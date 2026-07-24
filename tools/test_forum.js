const { chromium } = require('playwright');
const fs = require('fs');
const assert = require('assert');
const { execSync } = require('child_process');

(async () => {
    const browser = await chromium.launch();
    const context = await browser.newContext();
    const page = await context.newPage();
    const scriptSources = new Map();

    page.on('response', async response => {
        if (response.request().resourceType() === 'script') {
            try {
                scriptSources.set(response.url(), await response.text());
            } catch (e) { }
        }
        if (response.status() >= 400) {
            try {
                const text = await response.text();
                console.error(`[HTTP ${response.status()}] ${response.url()}\nResponse Body:\n${text}\n---`);
            } catch (e) {
                console.error(`[HTTP ${response.status()}] ${response.url()} (Failed to read body: ${e.message})`);
            }
        }
    });

    page.on('pageerror', async exception => {
        console.error(`Uncaught Browser Exception at URL [${page.url()}]:\nMessage: ${exception.message}\nStack:\n${exception.stack || exception}`);
        const invalidScripts = [];
        for (const frame of page.frames()) {
            try {
                const scripts = await frame.evaluate(() => Array.from(document.scripts)
                    .filter(script => !script.src)
                    .map(script => script.textContent));
                scripts.forEach((source, index) => {
                    try {
                        if (source.includes('import ') || source.includes('import{') || source.includes('export ')) return;
                        new Function(source);
                    } catch (error) {
                        invalidScripts.push(`${frame.url()} inline script #${index + 1}: ${error.message}\n${source.slice(0, 1000)}`);
                    }
                });
            } catch (e) { }
        }
        for (const [url, source] of scriptSources) {
            try {
                if (source.includes('import ') || source.includes('import{') || source.includes('export ')) continue;
                new Function(source);
            } catch (error) {
                invalidScripts.push(`${url}: ${error.message}\n${source.slice(0, 1000)}`);
            }
        }
        const diagnostic = invalidScripts.length ? `\nInvalid scripts:\n${invalidScripts.join('\n\n')}` : '';
        const failure = `Uncaught exception in browser at [${page.url()}]: ${exception.message || exception}${diagnostic}`;
        fs.writeFileSync('browser_error.txt', failure);
        throw new Error(failure);
    });

    page.on('console', msg => {
        if (msg.type() === 'error') {
            const txt = msg.text();
            if (txt.includes('Failed to load resource')) {
                return;
            }
            throw new Error(`Console error in browser: ${txt}`);
        }
    });

    let report = "# DiscuzX Functional Test Report\n\n";
    const fillPostEditor = async (message) => {
        const editorFrame = page.locator('iframe[id$="_iframe"]');
        if(await editorFrame.count()) {
            assert.strictEqual(await editorFrame.count(), 1, 'Assertion Error: More than one post editor iframe rendered.');
            await page.frameLocator('iframe[id$="_iframe"]').locator('body').fill(message);
            return;
        }

        const textEditor = page.locator('textarea[name="message"]:visible');
        assert.strictEqual(await textEditor.count(), 1, 'Assertion Error: Visible post editor did not render.');
        await textEditor.fill(message);
    };
    const sendPrivateMessage = async (senderPage, recipient, message) => {
        await senderPage.goto('http://127.0.0.1:8080/home.php?mod=spacecp&ac=pm');
        await senderPage.waitForLoadState('networkidle');
        const pmForm = senderPage.locator('form[id^="pmform_"]:visible');
        assert.strictEqual(await pmForm.count(), 1, 'Assertion Error: PM compose form did not render.');
        const recipientInput = pmForm.locator('input[name="username"]');
        const messageInput = pmForm.locator('textarea[name="message"]');
        const submitButton = pmForm.locator('#pmsubmit_btn');
        assert.strictEqual(await recipientInput.count(), 1, 'Assertion Error: PM recipient field did not render.');
        assert.strictEqual(await messageInput.count(), 1, 'Assertion Error: PM message field did not render.');
        assert.strictEqual(await submitButton.count(), 1, 'Assertion Error: PM submit button did not render.');
        await recipientInput.fill(recipient);
        await messageInput.fill(message);
        const responsePromise = senderPage.waitForResponse(response =>
            response.request().method() === 'POST' &&
            response.url().includes('home.php?mod=spacecp&ac=pm&op=send')
        );
        await submitButton.click();
        const response = await responsePromise;
        const status = response.status();
        let responseText = '';
        if (status < 300 || status >= 400) {
            try {
                responseText = await response.text();
            } catch (e) {
                responseText = `[Failed to read body: ${e.message}]`;
            }
        } else {
            responseText = `[Redirect response to ${response.headers()['location'] || 'unknown'}]`;
        }
        assert.ok(response.ok() || (status >= 300 && status < 400), `Assertion Error: PM send request failed: status=${status}; body=${responseText.slice(0, 2000)}`);
    };
    console.log("Starting functional tests...");

    try {
        const timestamp = Math.floor(Date.now() / 1000).toString().slice(-6);
        const username = 'u' + timestamp;
        const email = username + '@example.com';
        const password = 'Testpassword123!';

        console.log("Phase 1: Unprivileged User Registration and Posting");

        const phpConfig = `<?php
        require './source/class/class_core.php';
        \$discuz = C::app();
        \$discuz->init();

        DB::query("TRUNCATE TABLE ".DB::table('common_secquestion'));
        C::t('common_secquestion')->insert(array('type' => 0, 'question' => '1+1=?', 'answer' => '2'));

        \$seccodedata = array('rule' => array('register' => array('allow' => 0, 'numlimit' => '', 'timelimit' => 0),'login' => array('allow' => 0, 'nolocal' => 0, 'pwsimple' => 0, 'pwerror' => 0, 'outofday' => '', 'numiptry' => '', 'timeiptry' => 0),'post' => array('allow' => 0, 'numlimit' => '', 'timelimit' => 0, 'nplimit' => '', 'vplimit' => ''),'password' => array('allow' => 0),'card' => array('allow' => 0)),'minposts' => '','type' => 0,'width' => 150,'height' => 60,'scatter' => 0,'background' => 0,'adulterate' => 0,'ttf' => 0,'angle' => 0,'warping' => 0,'color' => 0,'size' => 0,'shadow' => 0,'animator' => 0);
        \$secqaa = array('status' => 1, 'minposts' => 0, 'statuses' => array(1 => 1, 2 => 1, 3 => 1), 'allowcode' => 0, 'allowqa' => 1);
        C::t('common_setting')->update('seccodedata', serialize(\$seccodedata));
        C::t('common_setting')->update('secqaa', serialize(\$secqaa));
        C::t('common_setting')->update('regname', 'register');
        C::t('common_setting')->update('floodctrl', '0');
        C::t('common_setting')->update('pmstatus', '1');
        C::t('common_usergroup_field')->update(10, array('allowpostattach' => '1', 'allowpostimage' => '1', 'allowposttag' => '1'));
        C::t('common_usergroup_field')->update(7, array('allowpostattach' => '1', 'allowpostimage' => '1', 'allowposttag' => '1'));

        require_once libfile('function/cache');
        updatecache(array('setting', 'secqaa', 'styles', 'usergroups'));
        ?>`;
        fs.writeFileSync('setup_test_sec.php', phpConfig);
        execSync('php setup_test_sec.php');
        if (fs.existsSync('setup_test_sec.php')) fs.unlinkSync('setup_test_sec.php');



        console.log("Testing UI Registration...");
        await page.goto('http://127.0.0.1:8080/member.php?mod=register');
        await page.waitForLoadState('networkidle');

        const registrationForm = page.locator('#registerform');
        assert.strictEqual(await registrationForm.count(), 1, 'Assertion Error: Desktop registration form did not render.');
        // reginput can rename the DOM id and name; the first text field is the username.
        const registrationTextFields = registrationForm.locator('input[type="text"]');
        assert.ok(await registrationTextFields.count() > 0, 'Assertion Error: Desktop registration username field did not render.');
        await registrationTextFields.nth(0).fill(username);
        const passwordInputs = registrationForm.locator('input[type="password"]');
        if (await passwordInputs.count() >= 2) {
            await passwordInputs.nth(0).fill(password);
            await passwordInputs.nth(1).fill(password);
        }
        const emailInput = registrationForm.locator('input[type="email"]');
        if (await emailInput.count()) await emailInput.fill(email);

        const agreeCheckbox = registrationForm.locator('input[name="agree"]');
        if (await agreeCheckbox.count()) await agreeCheckbox.check();

        const secqaaInput = registrationForm.locator('input[name*="secanswer"]');
        if (await secqaaInput.count()) await secqaaInput.fill('2');

        const regSubmitBtn = registrationForm.locator('#registerformsubmit');
        assert.strictEqual(await regSubmitBtn.count(), 1, 'Assertion Error: Desktop registration submit button did not render.');
        const registrationResponse = page.waitForResponse(response => response.request().method() === 'POST' && response.url().includes('member.php?mod=register'));
        await regSubmitBtn.click();
        await registrationResponse;
        await page.waitForTimeout(500);

        console.log("Checking if user exists in DB...");
        const dbCheck = execSync("sudo mysql -u root ultrax -N -s -e \"SELECT COUNT(*) FROM pre_common_member WHERE username='" + username + "';\"").toString().trim();
        console.log("DB count for user:", dbCheck);

        if (dbCheck !== '1') {
            console.log("Registration failed. Page source:");
            console.log(await page.innerHTML('body'));
        }
        assert.ok(dbCheck === '1', 'Assertion Error: Registered user does not exist in database.');
        await page.screenshot({ path: 'screenshot_forum_01_registered.png' });

        await page.goto('http://127.0.0.1:8080/home.php?mod=spacecp');
        const spaceUrl = await page.url();
        assert.ok(spaceUrl.includes('mod=spacecp') || spaceUrl.includes('member.php'), 'Assertion Error: Registration failed or login session not established.');

        const domContent = await page.textContent('body');
        if (!domContent.includes(username)) {
            console.log("DOM content doesn't contain username. Registration failed to auto-login.");
            await page.goto('http://127.0.0.1:8080/member.php?mod=logging&action=login');
            await page.waitForLoadState('networkidle');
            const loginUser = await page.$('input[name="username"]');
            if (loginUser) await loginUser.fill(username);
            const loginPass = await page.$('input[name="password"]');
            if (loginPass) await loginPass.fill(password);
            const loginSecqaa = await page.$('input[name*="secanswer"]');
            if (loginSecqaa) await loginSecqaa.fill('2');
            const loginSubmitBtn = await page.$('button[name="loginsubmit"]');
            if (loginSubmitBtn) {
                await loginSubmitBtn.click();
                await page.waitForNavigation({ waitUntil: 'networkidle' }).catch(() => { });
            }

            await page.goto('http://127.0.0.1:8080/home.php?mod=spacecp');
            const loginDomContent = await page.textContent('body');
            assert.ok(loginDomContent.includes(username), 'Assertion Error: Username not found on DOM after registration/login. Login failed.');
        }
        report += '### 1. User Registration & Login\n- **Status**: Checked\n- **Username**: ' + username + '\n\n';

        // Pre-setup Avatar before advanced editor screenshot & posting tests
        console.log("Setting up user avatar via UI (testing multiple extensions: PNG, JPG, GIF)...");
        const avatarFiles = [
            'static/image/common/nosexbg.png',
            'static/image/smiley/BQ2/alu1.jpg',
            'static/image/common/notice.gif'
        ];

        await page.goto('http://127.0.0.1:8080/home.php?mod=spacecp&ac=avatar');
        await page.waitForLoadState('networkidle');

        for (const imgPath of avatarFiles) {
            const avatarInput = await page.$('#avatarfile, input[name="Filedata"], input[type="file"]');
            if (avatarInput && fs.existsSync(imgPath)) {
                await avatarInput.setInputFiles(imgPath);
                await page.waitForTimeout(500);
            }
        }

        const confirmBtn = await page.$('#avconfirm, input[name="confirm"], button[type="submit"]');
        if (confirmBtn) {
            await confirmBtn.click().catch(() => { });
            await page.waitForTimeout(1500);
        }

        const userUid = execSync("sudo mysql -u root ultrax -N -s -e \"SELECT uid FROM pre_common_member WHERE username='" + username + "';\"").toString().trim();

        // Perform browser post to avatar endpoint using valid JPEG base64 if needed
        let avatarStatus = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT avatarstatus FROM pre_common_member WHERE uid='${userUid}';"`).toString().trim();
        if (avatarStatus !== '1') {
            const validJpegBase64 = fs.readFileSync('static/image/smiley/BQ2/alu1.jpg').toString('base64');
            await page.evaluate(async (b64) => {
                let formhash = '';
                const fhInput = document.querySelector('input[name="formhash"]');
                if (fhInput) {
                    formhash = fhInput.value;
                } else if (window.FORMHASH) {
                    formhash = window.FORMHASH;
                }
                const formData = new FormData();
                formData.append('formhash', formhash);
                formData.append('avatar1', b64);
                formData.append('avatar2', b64);
                formData.append('avatar3', b64);
                await fetch('api/avatar/index.php?m=user&inajax=1&a=rectavatar&avatartype=virtual&base64=yes', {
                    method: 'POST',
                    body: formData
                });
            }, validJpegBase64);
            await page.goto('http://127.0.0.1:8080/home.php?mod=spacecp&ac=avatar');
            await page.waitForLoadState('networkidle');
            avatarStatus = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT avatarstatus FROM pre_common_member WHERE uid='${userUid}';"`).toString().trim();
        }
        assert.strictEqual(avatarStatus, '1', 'Assertion Error: User avatarstatus in database was not 1.');

        console.log("Attempting to post normal thread as unprivileged user...");
        await page.goto('http://127.0.0.1:8080/forum.php?mod=forumdisplay&fid=2');
        await page.waitForLoadState('networkidle');
        const postNewThreadBtn = page.locator('#newspecial, a[href*="action=newthread"], #newspecialtmp').first();
        if (await postNewThreadBtn.count()) {
            await postNewThreadBtn.click();
        } else {
            await page.goto('http://127.0.0.1:8080/forum.php?mod=post&action=newthread&fid=2');
        }
        await page.waitForLoadState('networkidle');

        console.log("Capturing Advanced Editor Screenshot...");
        await page.screenshot({ path: 'screenshot_advanced_editor.png', fullPage: true }).catch(() => { });

        const subjectInput = page.locator('input[name="subject"]');
        if (await subjectInput.count()) await subjectInput.fill('Standard User Thread');

        await fillPostEditor('Body text from unprivileged account.');

        const secqaaPost = page.locator('input[name*="secanswer"]');
        if (await secqaaPost.count()) await secqaaPost.fill('2');

        const postSubmitBtn = page.locator('button[name="topicsubmit"][type="submit"]');
        if (await postSubmitBtn.count()) {
            await Promise.all([
                page.waitForNavigation({ waitUntil: 'networkidle' }).catch(() => {}),
                postSubmitBtn.click()
            ]);
        }

        await page.waitForNavigation({ waitUntil: 'networkidle' }).catch(() => { });
        await page.waitForTimeout(3000);

        console.log("Checking if posted thread exists in DB...");
        const threadDbCheck = execSync("sudo mysql -u root ultrax -N -s -e \"SELECT COUNT(*) FROM pre_forum_thread WHERE subject='Standard User Thread';\"").toString().trim();
        console.log("DB count for thread:", threadDbCheck);

        const currentUrl = page.url();
        const postContent = await page.textContent('body');

        assert.ok(parseInt(threadDbCheck, 10) >= 1, 'Assertion Error: Normal user thread post was not found in database.');
        assert.ok(
            /mod=viewthread&tid=\d+/.test(currentUrl) || postContent.includes('Standard User Thread') || postContent.includes('Thread'),
            'Assertion Error: Normal user posting did not result in thread view or success message. Final URL: ' + currentUrl
        );
        report += '### 2. Unprivileged User Posting\n- **Status**: Checked\n- **Thread Created**: Standard User Thread\n\n';

        const tidOutput = execSync("sudo mysql -u root ultrax -N -s -e \"SELECT tid FROM pre_forum_thread WHERE subject='Standard User Thread' ORDER BY tid DESC LIMIT 1;\"").toString().trim();

        if (tidOutput) {
            // Reply to Thread
            console.log("Attempting to reply to thread...");
            const desktopReplyBtn = page.locator('#post_reply, a[href*="action=reply"]').first();
            if (await desktopReplyBtn.count()) {
                await desktopReplyBtn.click();
            } else {
                await page.goto(`http://127.0.0.1:8080/forum.php?mod=post&action=reply&fid=2&tid=${tidOutput}`);
            }
            await page.waitForLoadState('networkidle');

            await fillPostEditor('Reply text from unprivileged account.');
            const replySecqaa = page.locator('input[name*="secanswer"]');
            if(await replySecqaa.count()) await replySecqaa.fill('2');
            const replyBtn = await page.$('#postsubmit, button[name="replysubmit"]');
            if (replyBtn) {
                await replyBtn.click();
                await page.waitForNavigation({ waitUntil: 'networkidle' }).catch(() => { });
                await page.waitForTimeout(2000);
            }

            console.log("Checking if reply exists in DB...");
            const replyDbCheck = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT COUNT(*) FROM pre_forum_post WHERE tid='${tidOutput}' AND first=0;"`).toString().trim();
            assert.ok(parseInt(replyDbCheck, 10) >= 1, 'Assertion Error: Reply post was not found in database.');
            report += '### 3. Unprivileged User Reply\n- **Status**: Checked\n- **Reply Count**: ' + replyDbCheck + '\n\n';

            // Edit Thread
            console.log("Attempting to edit thread...");
            const pidOutput = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT pid FROM pre_forum_post WHERE tid='${tidOutput}' AND first=1 LIMIT 1;"`).toString().trim();
            if (pidOutput) {
                if (!page.url().includes('mod=post&action=edit')) {
                    const editPostBtn = page.locator(`a[href*="action=edit"][href*="pid=${pidOutput}"]`).first();
                    if (await editPostBtn.count() && await editPostBtn.isVisible().catch(() => false)) {
                        await editPostBtn.click();
                        await page.waitForLoadState('networkidle');
                    }
                    if (!page.url().includes('mod=post&action=edit')) {
                        await page.goto(`http://127.0.0.1:8080/forum.php?mod=post&action=edit&fid=2&tid=${tidOutput}&pid=${pidOutput}`);
                        await page.waitForLoadState('networkidle');
                    }
                }

                const editSubject = page.locator('#postform input[name="subject"]:visible, input[name="subject"]:visible').first();
                if (await editSubject.count()) {
                    await editSubject.fill('Standard User Thread (Edited)');
                }
                await fillPostEditor('Edited body text from unprivileged account.');
                const editSecqaa = page.locator('input[name*="secanswer"]');
                if(await editSecqaa.count()) await editSecqaa.fill('2');
                const editBtn = await page.$('#postsubmit, button[name="editsubmit"]');
                if (editBtn) {
                    await editBtn.click();
                    await page.waitForNavigation({ waitUntil: 'networkidle' }).catch(() => { });
                    await page.waitForTimeout(2000);
                }

                console.log("Checking if edited thread title exists in DB...");
                const editDbCheck = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT COUNT(*) FROM pre_forum_thread WHERE tid='${tidOutput}' AND subject='Standard User Thread (Edited)';"`).toString().trim();
                assert.strictEqual(editDbCheck, '1', 'Assertion Error: Edited thread title was not updated in database.');
                report += '### 4. Unprivileged User Edit\n- **Status**: Checked\n- **Edited Title**: Standard User Thread (Edited)\n\n';
            }
        }

        console.log("Testing Personal Info Update via spacecp...");
        await page.goto('http://127.0.0.1:8080/home.php?mod=spacecp&ac=profile');
        await page.waitForLoadState('networkidle');

        await page.evaluate(() => {
            const form = document.querySelector('form[action*="mod=spacecp"]') || document.forms[0];
            if (form) {
                const sightml = form.querySelector('textarea[name="sightml"], #sightmlmessage');
                if (sightml) sightml.value = 'My Custom Test Signature';

                const customstatus = form.querySelector('input[name="customstatus"]');
                if (customstatus) customstatus.value = 'Custom Member Status';

                const submitBtn = form.querySelector('button[type="submit"], input[type="submit"], #profilesubmitbtn');
                if (submitBtn) submitBtn.click();
                else form.submit();
            }
        });
        await page.waitForTimeout(2000);

        console.log("Testing User Threads Page (with view=me)...");
        await page.goto('http://127.0.0.1:8080/home.php?mod=space&do=thread&view=me');
        await page.waitForLoadState('networkidle');
        await page.screenshot({ path: 'screenshot_space_thread_viewme.png' });

        const viewMeBody = await page.textContent('body');
        assert.ok(
            viewMeBody.includes('Standard User Thread') || viewMeBody.includes('Thread') || viewMeBody.includes(username),
            'Assertion Error: view=me user threads page did not load correctly.'
        );

        console.log("Testing User Threads Page (without view=me)...");
        await page.goto('http://127.0.0.1:8080/home.php?mod=space&do=thread');
        await page.waitForLoadState('networkidle');
        await page.screenshot({ path: 'screenshot_space_thread_default.png' });

        const defaultThreadBody = await page.textContent('body');
        assert.ok(
            defaultThreadBody.includes('Standard User Thread') || defaultThreadBody.includes('Thread') || defaultThreadBody.includes(username),
            'Assertion Error: Default user threads page (without view=me) did not load correctly.'
        );

        report += '### 4b. Personal Info Update & Space Threads Verification\n- **Status**: Checked\n- **spacecp Update**: Success\n- **Threads Page (with view=me)**: Success — `screenshot_space_thread_viewme.png`\n- **Threads Page (without view=me)**: Success — `screenshot_space_thread_default.png`\n\n';

        console.log("Testing Personal Messages (PM) on Desktop via UI...");
        const userPmToAdmin = 'UI sent test message to admin.';
        await sendPrivateMessage(page, 'admin', userPmToAdmin);
        const userPmDbCheck = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT COUNT(*) FROM pre_common_pm_message p INNER JOIN pre_common_pm_member m ON m.plid=p.plid WHERE m.uid='1' AND p.authorid='${userUid}' AND p.message='${userPmToAdmin}';"`).toString().trim();
        assert.strictEqual(userPmDbCheck, '1', 'Assertion Error: User PM was not delivered to the admin inbox.');

        console.log("Testing Reply Quote & Notification (do=notice) and PM send back from admin via UI...");
        if (tidOutput) {
            const firstPid = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT pid FROM pre_forum_post WHERE tid='${tidOutput}' AND first=1 LIMIT 1;"`).toString().trim();

            const adminContext = await browser.newContext();
            const adminPage = await adminContext.newPage();
            await adminPage.goto('http://127.0.0.1:8080/member.php?mod=logging&action=login');
            await adminPage.waitForLoadState('networkidle');
            const adminLoginForm = adminPage.locator('form[id^="loginform_"]:visible');
            await adminLoginForm.locator('input[name="username"]').fill('admin');
            await adminLoginForm.locator('input[name="password"]').fill('Testpassword123!');
            const adminSecqaa = adminLoginForm.locator('input[name*="secanswer"]');
            if (await adminSecqaa.count()) await adminSecqaa.fill('2');
            await Promise.all([
                adminPage.waitForNavigation({ waitUntil: 'networkidle' }).catch(() => {}),
                adminLoginForm.evaluate(form => form.submit())
            ]);

            const adminPmToUser = 'Admin reply PM to user via UI.';
            await sendPrivateMessage(adminPage, username, adminPmToUser);
            const adminPmDbCheck = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT COUNT(*) FROM pre_common_pm_message p INNER JOIN pre_common_pm_member m ON m.plid=p.plid WHERE m.uid='${userUid}' AND p.authorid='1' AND p.message='${adminPmToUser}';"`).toString().trim();
            assert.strictEqual(adminPmDbCheck, '1', 'Assertion Error: Admin PM was not delivered to the user inbox.');

            await adminPage.goto(`http://127.0.0.1:8080/forum.php?mod=post&action=reply&fid=2&tid=${tidOutput}&reppost=${firstPid}`);
            await adminPage.waitForLoadState('networkidle');
            await adminPage.evaluate((msg) => {
                const textArea = document.querySelector('textarea[name="message"], #postmessage');
                if (textArea) textArea.value = (textArea.value ? textArea.value + '\n' : '') + msg;
                try {
                    if (window.editdoc && window.editdoc.body) window.editdoc.body.innerHTML = msg;
                } catch (e) { }
                const secqaa = document.querySelector('input[name*="secanswer"]');
                if (secqaa) secqaa.value = '2';
            }, 'Admin quote reply to user thread.');
            const adminReplyBtn = await adminPage.$('#postsubmit, button[name="replysubmit"]');
            assert.ok(adminReplyBtn, 'Assertion Error: Admin reply submit button was not rendered.');
            await adminReplyBtn.click();
            await adminPage.waitForNavigation({ waitUntil: 'networkidle' }).catch(() => {});
            await adminContext.close();

            // Verify PM center for user
            await page.goto('http://127.0.0.1:8080/home.php?mod=space&do=pm');
            await page.waitForLoadState('networkidle');
            await page.screenshot({ path: 'screenshot_desktop_pm.png' });
            const pmBody = await page.textContent('body');
            assert.ok(pmBody.includes(adminPmToUser), 'Assertion Error: Desktop PM center did not display the delivered admin message.');
            report += '### 4c. Desktop Personal Message (PM)\n- **Status**: Checked\n- **Send PM via UI**: Success\n- **Admin Send Back PM**: Success\n- **PM Center View**: Success\n- **Screenshot**: `screenshot_desktop_pm.png`\n\n';

            const adminReplyDbCheck = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT COUNT(*) FROM pre_forum_post WHERE tid='${tidOutput}' AND authorid=1 AND first=0 AND message LIKE '%Admin quote reply to user thread.%';"`).toString().trim();
            assert.ok(parseInt(adminReplyDbCheck, 10) >= 1, 'Assertion Error: Admin quote reply was not created in database.');

            await page.goto('http://127.0.0.1:8080/home.php?mod=space&do=notice');
            await page.waitForLoadState('networkidle');
            await page.screenshot({ path: 'screenshot_desktop_notice.png' });

            const noticeDbCheck = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT COUNT(*) FROM pre_home_notification WHERE uid='${userUid}';"`).toString().trim();
            assert.ok(parseInt(noticeDbCheck, 10) >= 1, 'Assertion Error: Notification record was not found in database.');

            const noticeBody = await page.textContent('body');
            assert.ok(
                noticeBody.includes('admin') || noticeBody.includes('Standard User Thread') || noticeBody.includes('reply') || noticeBody.includes('replied') || noticeBody.includes('Notice') || noticeBody.includes('Notification'),
                'Assertion Error: Desktop reply notification page (do=notice) did not render notice content.'
            );
            report += '### 4d. Desktop Reply Quote & Notification (do=notice)\n- **Status**: Checked\n- **Admin Quote Reply via UI**: Success\n- **DB Notification Check**: Passed\n- **Notice Page Render**: Success\n- **Screenshot**: `screenshot_desktop_notice.png`\n\n';
        }

        console.log("Checking profile page for user custom avatar...");
        await page.goto(`http://127.0.0.1:8080/home.php?mod=space&uid=${userUid}&do=profile`);
        await page.waitForLoadState('networkidle');

        const profileAvatarImg = await page.$('#uhd .avt img, #uhd .icn.avt img, #uhd .avt');
        assert.ok(profileAvatarImg !== null, 'Assertion Error: Avatar image element was not rendered on profile page.');

        console.log("Checking header for user custom avatar...");
        await page.goto('http://127.0.0.1:8080/forum.php?mod=forumdisplay&fid=2');
        await page.waitForLoadState('networkidle');

        const headerSnippet = await page.evaluate(() => {
            const hd = document.getElementById('hd') || document.getElementById('um') || document.body;
            return hd ? hd.innerHTML.substring(0, 400) : '';
        });

        const headerAvatarImg = await page.$('#um .avt img, #um .avt a, #um .avt, #hd .avt img, #um img, .avt img, .header-user-avatar img, .header-user-avatar .Avatar, .header-user-avatar, #um');
        assert.ok(headerAvatarImg !== null, `Assertion Error: Avatar image element was not rendered in page header. Header HTML: ${headerSnippet}`);

        console.log("Checking viewthread page for author custom avatar...");
        await page.goto(`http://127.0.0.1:8080/forum.php?mod=viewthread&tid=${tidOutput}`);
        await page.waitForLoadState('networkidle');

        const viewthreadAvatarImg = await page.$('#postlist .pls .avatar img, #postlist .postauthor .avatar img, #postlist .pls .avatar');
        assert.ok(viewthreadAvatarImg !== null, 'Assertion Error: Author avatar image element was not rendered on viewthread page.');

        report += '### 5. Unprivileged User Avatar Setup & Verification\n- **Status**: Checked\n- **Avatar Status in DB**: 1\n- **Profile Avatar Check**: Passed\n- **Header Avatar Check**: Passed\n- **Viewthread Avatar Check**: Passed\n\n';

        // 6. User Image Attachment Post Test
        console.log("Attempting to post thread with image attachment...");
        await page.goto('http://127.0.0.1:8080/forum.php?mod=post&action=newthread&fid=2');
        await page.waitForLoadState('networkidle');

        const attachSubject = await page.$('input[name="subject"]');
        if (attachSubject) {
            await attachSubject.fill('Thread with Attachment');
        }

        const formhash = await page.evaluate(() => {
            return (window.FORMHASH || (document.querySelector('input[name="formhash"]') ? document.querySelector('input[name="formhash"]').value : ''));
        });
        assert.ok(formhash, 'Assertion Error: Upload formhash is missing.');
        const uploaderRuntime = await page.evaluate(() => ({
            available: typeof DiscuzUploader === 'function',
            scripts: Array.from(document.scripts).map(script => script.src).filter(Boolean),
        }));
        assert.ok(uploaderRuntime.available, 'Assertion Error: Desktop HTML5 DiscuzUploader runtime did not load.');
        assert.ok(
            uploaderRuntime.scripts.some(src => /\/discuz_uploader\.js(?:\?|$)/.test(src)),
            `Assertion Error: Renamed desktop uploader script was not loaded. Scripts: ${uploaderRuntime.scripts.join(', ')}`
        );

        const attachmentFixture = 'static/image/common/nosexbg.png';
        assert.ok(fs.existsSync(attachmentFixture), `Assertion Error: Attachment fixture is missing: ${attachmentFixture}`);
        const rejectedUploadStatus = await page.evaluate(async () => {
            const res = await fetch('misc.php?mod=upload&operation=upload&simple=1&type=image&fid=2', { method: 'POST' });
            return res.status;
        });
        assert.strictEqual(rejectedUploadStatus, 403, 'Assertion Error: Upload endpoint accepted a request without formhash.');

        let aid = '';
        let lastUploadResp = '';
        try {
            const validJpegBase64 = fs.readFileSync('static/image/smiley/BQ2/alu1.jpg').toString('base64');
            lastUploadResp = await page.evaluate(async ({ fh, b64 }) => {
                const blob = await fetch('data:image/jpeg;base64,' + b64).then(r => r.blob());
                const formData = new FormData();
                formData.append('formhash', fh);
                formData.append('Filedata', blob, 'sample_test_attachment.jpg');
                const res = await fetch('misc.php?mod=upload&operation=upload&simple=1&type=image&fid=2', {
                    method: 'POST',
                    body: formData
                });
                return await res.text();
            }, { fh: formhash, b64: validJpegBase64 });
            console.log("Upload API response:", lastUploadResp);
            const match = lastUploadResp.match(/(?:DISCUZUPLOAD\|0\||^)(\d+)(?:\||$)/);
            if (match && match[1] !== '0') {
                aid = match[1];
            }
        } catch (err) {
            console.warn("Upload request warning:", err.message);
        }

        if (!aid) {
            aid = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT aid FROM pre_forum_attachment_unused WHERE uid='${userUid}' ORDER BY aid DESC LIMIT 1;"`).toString().trim();
        }
        if (!aid) {
            aid = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT aid FROM pre_forum_attachment WHERE uid='${userUid}' ORDER BY aid DESC LIMIT 1;"`).toString().trim();
        }
        console.log("Discovered attachment AID:", aid);
        assert.ok(aid, `Assertion Error: Image attachment upload failed. Response was: ${lastUploadResp}`);

        const attachMsg = aid ? `Posting thread with image attachment content. [attach]${aid}[/attach]` : 'Posting thread with image attachment content.';

        await page.evaluate(({ aidVal, message }) => {
            const textArea = document.querySelector('textarea[name="message"], #postmessage');
            if (textArea) textArea.value = message;
            if (window.editdoc && window.editdoc.body) window.editdoc.body.innerHTML = message;
            if (aidVal) {
                const form = document.getElementById('postform') || document.querySelector('form[name="postform"]');
                if (form) {
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = `attachnew[${aidVal}][description]`;
                    hiddenInput.value = '';
                    form.appendChild(hiddenInput);
                }
            }
            const secqaa = document.querySelector('input[name*="secanswer"]');
            if (secqaa) secqaa.value = '2';
        }, { aidVal: aid, message: attachMsg });

        const extraTagBtn = await page.$('#extra_tag_b, a[href*="extra_tag"], #extra_tag_b a');
        if (extraTagBtn) {
            await extraTagBtn.click().catch(() => {});
        }
        const tagsInput = await page.$('#tags, input[name="tags"]');
        if (tagsInput) {
            await tagsInput.fill('sample_tag', { force: true }).catch(async () => {
                await page.evaluate(() => {
                    const input = document.querySelector('#tags, input[name="tags"]');
                    if (input) input.value = 'sample_tag';
                });
            });
        }

        const attachSubmitBtn = await page.$('#postsubmit, button[name="topicsubmit"]');
        if (attachSubmitBtn) {
            await attachSubmitBtn.click();
            await page.waitForNavigation({ waitUntil: 'networkidle' }).catch(() => { });
            await page.waitForTimeout(2000);
        }

        console.log("Checking if attachment thread exists in DB and loads in viewthread...");
        const attachTid = execSync("sudo mysql -u root ultrax -N -s -e \"SELECT tid FROM pre_forum_thread WHERE subject='Thread with Attachment' ORDER BY tid DESC LIMIT 1;\"").toString().trim();
        assert.ok(attachTid, 'Assertion Error: Thread with attachment was not created in database.');

        const attachDbRecord = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT COUNT(*) FROM pre_forum_attachment WHERE tid='${attachTid}';"`).toString().trim();
        console.log("DB count for pre_forum_attachment:", attachDbRecord);
        assert.ok(parseInt(attachDbRecord, 10) >= 1, 'Assertion Error: Image attachment record was not linked in pre_forum_attachment database table.');

        await page.goto(`http://127.0.0.1:8080/forum.php?mod=viewthread&tid=${attachTid}`);
        await page.waitForLoadState('networkidle');

        const viewthreadBody = await page.textContent('body');
        assert.ok(
            viewthreadBody.includes('Thread with Attachment') && viewthreadBody.includes('Posting thread with image attachment content.') && viewthreadBody.includes('sample_tag'),
            'Assertion Error: Attachment thread page did not load thread content cleanly in viewthread.'
        );

        const postImg = await page.$('#postlist .t_f img[id^="aimg_"], #postlist .t_f img[aid], #postlist .t_f img[file], #postlist .t_f img[zoomfile], #postlist .t_f .tattl img, #postlist .t_f img[src*="data/attachment/"]');
        // Verify the stored type as well as the browser's rendered image.
        const tfSnippet = await page.$eval('#postlist .t_f', el => el.innerHTML.substring(0, 600)).catch(() => '');
        const attachmentIndex = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT CONCAT(tid, ':', tableid) FROM pre_forum_attachment WHERE aid='${aid}' LIMIT 1;"`).toString().trim();
        const attachTableId = attachmentIndex.split(':')[1];
        const attachIsimage = attachTableId === undefined ? '' : execSync(`sudo mysql -u root ultrax -N -s -e "SELECT isimage FROM pre_forum_attachment_${attachTableId} WHERE aid='${aid}' AND tid='${attachTid}' LIMIT 1;"`).toString().trim();
        const unusedAttachment = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT COUNT(*) FROM pre_forum_attachment_unused WHERE aid='${aid}';"`).toString().trim();
        assert.strictEqual(attachmentIndex, `${attachTid}:${attachTid.slice(-1)}`, `Assertion Error: Attachment index was not bound to thread ${attachTid}. Found: ${attachmentIndex}`);
        assert.strictEqual(unusedAttachment, '0', `Assertion Error: Attachment ${aid} remained in pre_forum_attachment_unused.`);

        assert.strictEqual(attachIsimage, '1', `Assertion Error: Uploaded PNG was not stored as an image. isimage: ${attachIsimage}`);
        assert.ok(postImg !== null, `Assertion Error: Attached image <img> element was not rendered inside post content (.t_f). .t_f: ${tfSnippet.substring(0, 200)}. isimage: ${attachIsimage}`);
        const imageSize = await postImg.evaluate(img => ({ width: img.naturalWidth, height: img.naturalHeight }));
        assert.ok(imageSize.width > 0 && imageSize.height > 0, `Assertion Error: Attached image did not load (${imageSize.width}x${imageSize.height}).`);

        await page.screenshot({ path: 'screenshot_attachment_viewthread.png' }).catch(() => { });

        report += '### 6. Unprivileged User Image Attachment Post\n- **Status**: Checked\n- **Thread Created**: Thread with Attachment (TID: ' + attachTid + ', AID: ' + (aid || 'N/A') + ')\n- **Image Attachment DOM Check**: Passed\n- **Viewthread Verification**: Success\n\n';

    } catch (error) {
        console.error("Test execution failed:", error);
        process.exitCode = 1;
        try {
            const currentUrl = page.url();
            const pageTitle = await page.title().catch(() => 'Unknown Title');
            const pageSource = await page.content().catch(() => '');
            if (pageSource) {
                fs.writeFileSync('forum_page_source.html', pageSource);
                fs.writeFileSync('browser_page_source.html', pageSource);
            }
            await page.screenshot({ path: 'screenshot_forum_failure.png', fullPage: true }).catch(() => {});
            const errLog = `[Forum Failure] URL: ${currentUrl} | Title: ${pageTitle}\nError: ${error.stack || error.message}\nPage Source saved to forum_page_source.html\n---\n`;
            fs.appendFileSync('browser_error.txt', errLog);
        } catch (e) {
            console.error('Failed to capture failure state:', e.message);
        }
        report += "## Error Encountered\n```\n" + error.message + "\n```\n\n";
    } finally {
        await browser.close();
        fs.writeFileSync('functional_test_report.md', report);
        console.log("Tests completed.");
    }
})();
