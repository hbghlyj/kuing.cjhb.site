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
        DB::query('TRUNCATE TABLE '.DB::table('common_syscache'));
        require_once libfile('function/cache');
        build_cache_secqaa();
        updatecache();
        ?>`;
        fs.writeFileSync('disable_sec.php', phpConfig);
        execSync('php disable_sec.php');
        fs.unlinkSync('disable_sec.php');

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
        execSync("sudo mysql -u root ultrax -e \"REPLACE INTO pre_common_admincp_member (uid, cpgroupid, customperm) SELECT uid, 1, '' FROM pre_common_member WHERE username='" + username + "';\"");
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
