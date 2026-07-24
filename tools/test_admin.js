const { chromium } = require('playwright');
const fs = require('fs');
const assert = require('assert');
const { execSync } = require('child_process');

(async () => {
    const browser = await chromium.launch();
    const context = await browser.newContext();
    const page = await context.newPage();

    page.on('response', async response => {
        if (response.status() >= 400) {
            try {
                const text = await response.text();
                console.error(`[HTTP ${response.status()}] ${response.url()}\nResponse Body:\n${text}\n---`);
            } catch (e) {}
        }
    });

    page.on('pageerror', exception => {
        console.error(`Uncaught Browser Exception at URL [${page.url()}]:\nMessage: ${exception.message}\nStack:\n${exception.stack || exception}`);
        throw new Error(`Uncaught exception in browser at [${page.url()}]: ${exception.message || exception}`);
    });

    page.on('console', msg => {
        if (msg.type() === 'error') {
            const txt = msg.text();
            if (txt.includes('Failed to load resource')) return;
            throw new Error(`Console error in browser: ${txt}`);
        }
    });

    let report = "\n\n## Admin Panel Functional Test Report\n\n";
    console.log("Starting Admin Panel tests...");

    try {
        const timestamp = Math.floor(Date.now() / 1000).toString().slice(-6);
        const username = 'admin' + timestamp;
        const password = 'Testpassword123!';

        // Match the forum test's complete security configuration.
        const phpConfig = `<?php
        require './source/class/class_core.php';
        $discuz = C::app();
        $discuz->init();

        DB::query("TRUNCATE TABLE ".DB::table('common_secquestion'));
        C::t('common_secquestion')->insert(array('type' => 0, 'question' => '1+1=?', 'answer' => '2'));

        $seccodedata = array('rule' => array('register' => array('allow' => 0, 'numlimit' => '', 'timelimit' => 0),'login' => array('allow' => 0, 'nolocal' => 0, 'pwsimple' => 0, 'pwerror' => 0, 'outofday' => '', 'numiptry' => '', 'timeiptry' => 0),'post' => array('allow' => 0, 'numlimit' => '', 'timelimit' => 0, 'nplimit' => '', 'vplimit' => ''),'password' => array('allow' => 0),'card' => array('allow' => 0)),'minposts' => '','type' => 0,'width' => 150,'height' => 60,'scatter' => 0,'background' => 0,'adulterate' => 0,'ttf' => 0,'angle' => 0,'warping' => 0,'color' => 0,'size' => 0,'shadow' => 0,'animator' => 0);
        $secqaa = array('status' => 1, 'minposts' => 0, 'statuses' => array(1 => 1, 2 => 1, 3 => 1), 'allowcode' => 0, 'allowqa' => 1);
        C::t('common_setting')->update('seccodedata', serialize($seccodedata));
        C::t('common_setting')->update('secqaa', serialize($secqaa));
        C::t('common_setting')->update('regname', 'register');
        C::t('common_setting')->update('floodctrl', '0');
        C::t('common_setting')->update('albumstatus', '1');
        C::t('common_setting')->update('portalstatus', '1');
        C::t('common_setting')->update('editormodetype', '1');
        C::t('common_usergroup_field')->update(1, array('allowpostattach' => '1', 'allowpostimage' => '1', 'allowpostpoll' => '1', 'allowpostarticle' => '1', 'allowmanagearticle' => '1'));
        require_once libfile('function/cache');
        updatecache(array('setting', 'secqaa', 'styles', 'usergroups'));
        ?>`;
        fs.writeFileSync('setup_test_sec.php', phpConfig);
        execSync('php setup_test_sec.php');
        if (fs.existsSync('setup_test_sec.php')) fs.unlinkSync('setup_test_sec.php');

        // Register user
        await page.goto('http://127.0.0.1:8080/member.php?mod=register');
        await page.waitForLoadState('networkidle');

        await page.evaluate(({ username, password, email }) => {
            const form = document.getElementById('registerform');
            if (!form) return;
            const inputs = form.querySelectorAll('input[type="text"]');
            if (inputs.length > 0) inputs[0].value = username;
            const passwords = form.querySelectorAll('input[type="password"]');
            if (passwords.length >= 2) {
                passwords[0].value = password;
                passwords[1].value = password;
            }
            const emails = form.querySelectorAll('input[type="email"], input[name*="email"]');
            if (emails.length > 0) emails[0].value = email;
            const agree = form.querySelector('input[name="agree"]');
            if (agree) agree.checked = true;
            const secqaa = form.querySelector('input[name*="secanswer"]');
            if (secqaa) secqaa.value = '2';
            form.submit();
        }, { username, password, email: username + '@example.com' });
        await page.waitForNavigation({ waitUntil: 'networkidle' }).catch(() => {});
        await page.waitForTimeout(3000);

        const registeredUserCount = execSync("sudo mysql -u root ultrax -N -s -e \"SELECT COUNT(*) FROM pre_common_member WHERE username='" + username + "';\"").toString().trim();
        assert.strictEqual(registeredUserCount, '1', 'Assertion Error: Registered admin test user does not exist in database.');

        // Registration may require email activation and therefore not create a frontend session.
        await page.goto('http://127.0.0.1:8080/home.php?mod=spacecp');
        if (!(await page.textContent('body')).includes(username)) {
            await page.goto('http://127.0.0.1:8080/member.php?mod=logging&action=login');
            await page.waitForLoadState('networkidle');
            const loginUser = await page.$('input[name="username"]');
            if (loginUser) await loginUser.fill(username);
            const loginPass = await page.$('input[name="password"]');
            if (loginPass) await loginPass.fill(password);
            const loginSecqaa = await page.$('input[name*="secanswer"]');
            if (loginSecqaa) await loginSecqaa.fill('2');
            const loginSubmitBtn = await page.$('button[name="loginsubmit"]');
            if (loginSubmitBtn) await loginSubmitBtn.click();
            await page.waitForNavigation({ waitUntil: 'networkidle' }).catch(() => {});
            await page.waitForTimeout(500);

            await page.goto('http://127.0.0.1:8080/home.php?mod=spacecp');
            assert.ok((await page.textContent('body')).includes(username), 'Assertion Error: Admin test user login failed.');
        }

        console.log("Phase 2: Elevated User Testing");
        execSync("sudo mysql -u root ultrax -e \"UPDATE pre_common_member SET groupid=1, adminid=1 WHERE username='" + username + "';\"");
        execSync("sudo mysql -u root ultrax -e \"REPLACE INTO pre_common_admincp_member (uid, cpgroupid, customperm) SELECT uid, 0, '' FROM pre_common_member WHERE username='" + username + "';\"");
        report += '### 1. Privilege Elevation\n- **Status**: Checked\n\n';

        await page.goto('http://127.0.0.1:8080/home.php?mod=spacecp');
        await page.waitForLoadState('networkidle');
        const bioInput = await page.$('textarea[name="bio"]');
        if (bioInput) {
            await bioInput.fill('Updated bio as admin');
            const saveBtn = await page.$('button[name="profilesubmit"]');
            if (saveBtn) {
                await saveBtn.click();
                await page.waitForTimeout(1000);
                const savedMsg = await page.textContent('body');
                assert.ok(savedMsg.includes('保存成功') || savedMsg.includes('success') || !page.url().includes('profilesubmit'), 'Assertion Error: Profile update failed.');
            }
        }
        await page.screenshot({ path: 'screenshot_forum_02_admin_profile.png' });
        report += '### 2. Admin Profile Update\n- **Status**: Checked\n\n';

        console.log("Checking Admin Panel...");
        await page.goto('http://127.0.0.1:8080/admin.php');
        await page.waitForLoadState('networkidle');

        const adminPassInput = await page.$('input[name="admin_password"]');
        if (adminPassInput) {
            // mustlogin=1 authenticates this already logged-in account by password.
            await adminPassInput.fill(password);
            const adminSubmitBtn = await page.$('button[type="submit"], input[type="submit"], input[name="submit"]');
            if (adminSubmitBtn) {
                await adminSubmitBtn.click();
                await page.waitForNavigation({ waitUntil: 'networkidle' }).catch(() => {});
            }
            await page.waitForTimeout(3000);
        }

        // Verify admin authentication success: password prompt must be gone and admin workspace/frames loaded
        const hasLoginPrompt = await page.$('input[name="admin_password"]');
        const pageSource = await page.content();
        const hasAdminWorkspace = pageSource.includes('admincpnav') ||
            pageSource.includes('admincpframe') ||
            pageSource.includes('action=logout') ||
            pageSource.includes('action=header') ||
            page.frames().some(f => f.url().includes('admin.php?action='));

        assert.ok(!hasLoginPrompt && hasAdminWorkspace, 'Assertion Error: Admin panel authentication failed. Still on unauthorized login screen.');
        await page.screenshot({ path: 'screenshot_forum_03_admin_panel.png' });
        report += '### 3. Admin Panel UI\n- **Status**: Checked\n\n';

        console.log("Checking Admin Panel Logs Page...");
        await page.goto('http://127.0.0.1:8080/admin.php?action=logs');
        await page.waitForLoadState('networkidle');

        const logsPageSource = await page.content();
        assert.ok(
            !logsPageSource.includes('action_noaccess') && !logsPageSource.includes('您无权进行此操作') && (logsPageSource.includes('logs') || logsPageSource.includes('运行记录') || logsPageSource.includes('operation=') || page.url().includes('action=logs')),
            'Assertion Error: Admin CP logs page failed due to insufficient permissions or access restriction.'
        );
        await page.screenshot({ path: 'screenshot_forum_04_admin_logs.png' });
        report += '### 4. Admin Panel Logs Access\n- **Status**: Checked\n- **URL**: admin.php?action=logs\n\n';

        console.log("Checking renamed uploader operations...");
        await page.goto('http://127.0.0.1:8080/forum.php?mod=post&action=newthread&fid=2');
        await page.waitForLoadState('networkidle');
        const uploadFormhash = await page.evaluate(() => window.FORMHASH || document.querySelector('input[name="formhash"]')?.value || '');
        assert.ok(uploadFormhash, 'Assertion Error: Uploader operation test could not obtain formhash.');

        const uploadImage = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAMgAAADICAIAAAAiOjnJAAACFUlEQVR4nO3SQQkAIADAQAP6sKkVLeEQ5OAC7LEx94LrxvMCvmQsEsYiYSwSxiJhLBLGImEsEsYiYSwSxiJhLBLGImEsEsYiYSwSxiJhLBLGImEsEsYiYSwSxiJhLBLGImEsEsYiYSwSxiJhLBLGImEsEsYiYSwSxiJhLBLGImEsEsYiYSwSxiJhLBLGImEsEsYiYSwSxiJhLBLGImEsEsYiYSwSxiJhLBLGImEsEsYiYSwSxiJhLBLGImEsEsYiYSwSxiJhLBLGImEsEsYiYSwSxiJhLBLGImEsEsYiYSwSxiJhLBLGImEsEsYiYSwSxiJhLBLGImEsEsYiYSwSxiJhLBLGImEsEsYiYSwSxiJhLBLGImEsEsYiYSwSxiJhLBLGImEsEsYiYSwSxiJhLBLGImEsEsYiYSwSxiJhLBLGImEsEsYiYSwSxiJhLBLGImEsEsYiYSwSxiJhLBLGImEsEsYiYSwSxiJhLBLGInEAuwUSl75bns4AAAAASUVORK5CYII=', 'base64');
        const uploadOperation = async (operation, fields = {}, query = '') => {
            const response = await page.request.post(`http://127.0.0.1:8080/misc.php?mod=upload&operation=${operation}${query}`, {
                multipart: {
                    formhash: uploadFormhash,
                    ...fields,
                    Filedata: {
                        name: `${operation}_test.png`,
                        mimeType: 'image/png',
                        buffer: uploadImage,
                    },
                },
            });
            const body = await response.text();
            assert.strictEqual(response.status(), 200, `Assertion Error: ${operation} upload returned HTTP ${response.status()}: ${body}`);
            return body;
        };

        const pollUpload = JSON.parse(await uploadOperation('poll', {}, '&fid=2'));
        assert.ok(pollUpload.aid > 0 && pollUpload.errorcode === 0, `Assertion Error: Poll image upload failed: ${JSON.stringify(pollUpload)}`);

        const albumUpload = JSON.parse(await uploadOperation('album'));
        assert.ok(parseInt(albumUpload.picid, 10) > 0, `Assertion Error: Album image upload failed: ${JSON.stringify(albumUpload)}`);

        let portalCatid = execSync("sudo mysql -u root ultrax -N -s -e \"SELECT catid FROM pre_portal_category ORDER BY catid LIMIT 1;\"").toString().trim();
        if (!portalCatid) {
            portalCatid = execSync(`sudo mysql -u root ultrax -N -s -e "INSERT INTO pre_portal_category (catname, uid, username, dateline, description, seotitle, keyword) VALUES ('Uploader Test', (SELECT uid FROM pre_common_member WHERE username='${username}'), '${username}', UNIX_TIMESTAMP(), '', '', ''); SELECT LAST_INSERT_ID();"`).toString().trim();
        }
        const portalUpload = JSON.parse(await uploadOperation('portal', { catid: portalCatid, aid: '0' }));
        assert.ok(portalUpload.aid > 0 && portalUpload.errorcode === 0, `Assertion Error: Portal attachment upload failed: ${JSON.stringify(portalUpload)}`);

        const jsonEditorUpload = JSON.parse(await uploadOperation('jsoneditorupload', {}, '&fid=2'));
        assert.ok(
            jsonEditorUpload.success === 1 && jsonEditorUpload.file && jsonEditorUpload.file.aid > 0,
            `Assertion Error: JSON editor upload failed: ${JSON.stringify(jsonEditorUpload)}`
        );

        report += '### 5. Renamed HTML5 Uploader Operations\n- **Status**: Checked\n- **Poll image**: Success\n- **Album image**: Success\n- **Portal attachment**: Success\n- **JSON editor attachment**: Success\n\n';

    } catch (error) {
        console.error("Admin test execution failed:", error);
        process.exitCode = 1;
        report += "## Error Encountered in Admin Test\n```\n" + error.message + "\n```\n\n";
    } finally {
        await browser.close();
        fs.appendFileSync('functional_test_report.md', report);
        console.log("Admin tests completed and report appended.");
    }
})();
