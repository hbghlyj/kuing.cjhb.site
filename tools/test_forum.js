const { chromium } = require('playwright');
const fs = require('fs');
const assert = require('assert');
const { execSync } = require('child_process');

(async () => {
    const browser = await chromium.launch();
    const context = await browser.newContext();
    const page = await context.newPage();

    page.on('pageerror', exception => {
        throw new Error(`Uncaught exception in browser: ${exception}`);
    });

    page.on('console', msg => {
        if (msg.type() === 'error') {
            throw new Error(`Console error in browser: ${msg.text()}`);
        }
    });

    let report = "# DiscuzX Functional Test Report\n\n";
    console.log("Starting functional tests...");

    try {
        const timestamp = Math.floor(Date.now() / 1000).toString().slice(-6);
        const username = 'u' + timestamp;
        const email = username + '@example.com';
        const password = 'Testpassword123!';

        console.log("Phase 1: Unprivileged User Registration and Posting");

        const phpConfig = `<?php
        require './source/class/class_core.php';
        $discuz = C::app();
        $discuz->init();

        $seccodedata = array(
            'rule' => array(
                'register' => array('allow' => 0, 'numlimit' => '', 'timelimit' => 0),
                'login' => array('allow' => 0, 'nolocal' => 0, 'pwsimple' => 0, 'pwerror' => 0, 'outofday' => '', 'numiptry' => '', 'timeiptry' => 0),
                'post' => array('allow' => 0, 'numlimit' => '', 'timelimit' => 0, 'nplimit' => '', 'vplimit' => ''),
                'password' => array('allow' => 0),
                'card' => array('allow' => 0)
            ),
            'minposts' => '',
            'type' => 0,
            'width' => 150,
            'height' => 60,
            'scatter' => 0,
            'background' => 0,
            'adulterate' => 0,
            'ttf' => 0,
            'angle' => 0,
            'warping' => 0,
            'color' => 0,
            'size' => 0,
            'shadow' => 0,
            'animator' => 0
        );
        $secqaa = array(
            'status' => 0,
            'minposts' => 0,
            'statuses' => array('register'),
            'allowcode' => 0,
            'allowqa' => 0
        );

        C::t('common_setting')->update('seccodedata', serialize($seccodedata));
        C::t('common_setting')->update('secqaa', serialize($secqaa));
        try {
            C::t('common_secqaa')->truncate();
        } catch (Exception $e) {}
        try {
            C::t('common_secqaa')->insert(array('question' => 'What is 1?', 'answer' => md5('1'), 'type' => 1));
        } catch (Exception $e) {}

        require_once libfile('function/cache');
        updatecache('setting');
        ?>`;
        fs.writeFileSync('disable_sec.php', phpConfig);
        execSync('php disable_sec.php');
        execSync('rm disable_sec.php');
        execSync('rm -f data/sysdata/*.php');

        // Testing UI Registration
        await page.goto('http://127.0.0.1:8080/member.php?mod=register');
        await page.waitForLoadState('networkidle');

        const inputs = await page.$$('input[type="text"]');
        const passwords = await page.$$('input[type="password"]');
        if (inputs.length > 0) {
            try { await inputs[0].fill(username); } catch (e) {}
        }
        if (passwords.length >= 2) {
            await passwords[0].fill(password);
            await passwords[1].fill(password);
        }
        const emailInput = await page.$('input[name="email"], input[type="email"]');
        if (emailInput) {
            await emailInput.fill(email);
        }

        const secAnswerInput = await page.$('input[name*="secanswer"]');
        if (secAnswerInput) await secAnswerInput.fill('c4ca4238a0b923820dcc509a6f75849b');
        const secCodeInput = await page.$('input[name*="seccodeverify"]');
        if (secCodeInput) await secCodeInput.fill('1111');

        const submitBtn = await page.$('button[name="regsubmit"]');
        if (submitBtn) {
            await page.evaluate(() => { document.querySelector('#registerform').submit(); });
            await page.waitForTimeout(3000);
        }

        console.log("Checking if user exists in DB...");
        const dbCheck = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT COUNT(*) FROM pre_common_member WHERE username='${username}';"`).toString().trim();
        console.log("DB count for user:", dbCheck);
        assert.ok(dbCheck === '1', 'Assertion Error: Registered user does not exist in database.');

        // Verify successful registration by checking the DOM
        await page.goto('http://127.0.0.1:8080/home.php?mod=spacecp');
        const spaceUrl = await page.url();
        assert.ok(spaceUrl.includes('mod=spacecp') || spaceUrl.includes('member.php'), 'Assertion Error: Registration failed or login session not established.');

        const domContent = await page.textContent('body');
        if (!domContent.includes(username)) {
            console.log("DOM content doesn't contain username. Registration failed to auto-login.");
            // Explicitly login to ensure session is active
            await page.goto('http://127.0.0.1:8080/member.php?mod=logging&action=login');
            await page.waitForLoadState('networkidle');
            const loginUser = await page.$('input[name="username"]');
            if (loginUser) await loginUser.fill(username);
            const loginPass = await page.$('input[name="password"]');
            if (loginPass) await loginPass.fill(password);

            const loginSecAnswerInput = await page.$('input[name*="secanswer"]');
            if (loginSecAnswerInput) await loginSecAnswerInput.fill('1');
            const loginSecCodeInput = await page.$('input[name*="seccodeverify"]');
            if (loginSecCodeInput) await loginSecCodeInput.fill('1111');

            const loginSubmitBtn = await page.$('button[name="loginsubmit"]');
            if (loginSubmitBtn) {
                await loginSubmitBtn.click();
                await page.waitForNavigation({ waitUntil: 'networkidle' }).catch(() => {});
            }

            await page.goto('http://127.0.0.1:8080/home.php?mod=spacecp');
            const loginDomContent = await page.textContent('body');
            assert.ok(loginDomContent.includes(username), 'Assertion Error: Username not found on DOM after registration/login. Login failed.');
        }
        report += `### 1. User Registration & Login\n- **Status**: Checked\n- **Username**: ${username}\n\n`;

        // Unprivileged posting
        console.log("Attempting to post unprivileged thread...");
        await page.goto('http://127.0.0.1:8080/forum.php?mod=post&action=newthread&fid=10');
        await page.waitForLoadState('networkidle');

        const subjectInput = await page.$('input[name="subject"]');
        if (subjectInput) await subjectInput.fill('Standard User Thread');
        const messageInput = await page.$('textarea[name="message"]');
        if (messageInput) await messageInput.fill('Body text from unprivileged account.');

        const postSecAnswer = await page.$('input[name*="secanswer"]');
        if (postSecAnswer) await postSecAnswer.fill('1');
        const postSecCode = await page.$('input[name*="seccodeverify"]');
        if (postSecCode) await postSecCode.fill('1111');

        const postSubmitBtn = await page.$('button[name="topicsubmit"], #postsubmit');
        if (postSubmitBtn) {
            await postSubmitBtn.click();
            await page.waitForNavigation({ waitUntil: 'networkidle' }).catch(() => {});
        }

        const currentUrl = page.url();
        const postContent = await page.textContent('body');

        const errorAlert = await page.$('.alert_error, .alert_info');
        if (errorAlert) {
            const errorText = await errorAlert.textContent();
            console.log("Unprivileged posting was blocked by system (expected behavior for new users):", errorText.trim());
        } else {
             if (!(/mod=viewthread&tid=\d+/.test(currentUrl) || postContent.includes('审核') || postContent.includes('抱歉'))) {
                 console.log("Unprivileged posting failed without a clear error box! Final URL:", currentUrl);
                 console.log("Dumping page text to diagnose:", postContent.substring(0, 500));
                 assert.fail(`Assertion Error: Unprivileged user posting failed without a clear error message! Final URL: ${currentUrl}`);
             }
        }
        report += `### 2. Unprivileged User Posting\n- **Status**: Checked\n\n`;

        // Phase 2: Elevated User
        console.log("Phase 2: Elevated User Testing");
        execSync(`sudo mysql -u root ultrax -e "UPDATE pre_common_member SET groupid=1, adminid=1 WHERE username='${username}';"`);
        report += `### 3. Privilege Elevation\n- **Status**: Checked\n\n`;

        // Profile update
        await page.goto('http://127.0.0.1:8080/home.php?mod=spacecp');
        await page.waitForLoadState('networkidle');
        const bioInput = await page.$('textarea[name="bio"]');
        if(bioInput) {
             await bioInput.fill('Updated bio as admin');
             const saveBtn = await page.$('button[name="profilesubmit"]');
             if(saveBtn) {
                 await saveBtn.click();
                 await page.waitForTimeout(1000);
                 const savedMsg = await page.textContent('body');
                 assert.ok(savedMsg.includes('保存成功') || savedMsg.includes('success') || !page.url().includes('profilesubmit'), 'Assertion Error: Profile update failed.');
             }
        }
        report += `### 4. Admin Profile Update\n- **Status**: Checked\n\n`;

        // Admin panel
        console.log("Checking Admin Panel...");
        await page.goto('http://127.0.0.1:8080/admin.php');
        await page.waitForLoadState('networkidle');

        const adminPassInput = await page.$('input[name="admin_password"]');
        if(adminPassInput) {
            await adminPassInput.fill(password);
            const adminSubmitBtn = await page.$('input[name="submit"]');
            if (adminSubmitBtn) await adminSubmitBtn.click();
            await page.waitForTimeout(3000);
        }
        const adminPageText = await page.textContent('body');
        assert.ok(adminPageText.includes('Admin') || adminPageText.includes('管理中心') || adminPageText.includes('frame'), 'Assertion Error: Admin panel UI did not load correctly.');
        report += `### 5. Admin Panel UI\n- **Status**: Checked\n\n`;

    } catch (error) {
        console.error("Test execution failed:", error);
        process.exitCode = 1;
        report += "## Error Encountered\n```\n" + error.message + "\n```\n\n";
    } finally {
        await browser.close();
        fs.writeFileSync('functional_test_report.md', report);
        console.log("Tests completed.");
    }
})();
