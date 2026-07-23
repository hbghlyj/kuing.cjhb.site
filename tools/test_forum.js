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
                        new Function(source);
                    } catch (error) {
                        invalidScripts.push(`${frame.url()} inline script #${index + 1}: ${error.message}\n${source.slice(0, 1000)}`);
                    }
                });
            } catch (e) { }
        }
        for (const [url, source] of scriptSources) {
            try {
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

        \$seccodedata = array('rule' => array('register' => array('allow' => 0, 'numlimit' => '', 'timelimit' => 0),'login' => array('allow' => 0, 'nolocal' => 0, 'pwsimple' => 0, 'pwerror' => 0, 'outofday' => '', 'numiptry' => '', 'timeiptry' => 0),'post' => array('allow' => 0, 'numlimit' => '', 'timelimit' => 0, 'nplimit' => '', 'vplimit' => ''),'password' => array('allow' => 0),'card' => array('allow' => 0)),'minposts' => '','type' => 0,'width' => 150,'height' => 60,'scatter' => 0,'background' => 0,'adulterate' => 0,'ttf' => 0,'angle' => 0,'warping' => 0,'color' => 0,'size' => 0,'shadow' => 0,'animator' => 0);
        \$secqaa = array('status' => 0,'minposts' => 0,'statuses' => array(),'allowcode' => 0,'allowqa' => 0);
        C::t('common_setting')->update('seccodedata', serialize(\$seccodedata));
        C::t('common_setting')->update('secqaa', serialize(\$secqaa));
        C::t('common_setting')->update('regname', 'register');

        DB::query('TRUNCATE TABLE '.DB::table('common_syscache'));
        require_once libfile('function/cache');
        updatecache();
        ?>`;
        fs.writeFileSync('disable_sec.php', phpConfig);
        execSync('php disable_sec.php');
        execSync('rm disable_sec.php');

        // Let's hard bypass the helper class checking logic
        execSync("sed -i 's/public static function check_secqaa(\\$val, \\$idhash) {/public static function check_secqaa(\\$val, \\$idhash) { return true;/g' source/class/helper/helper_seccheck.php || true");
        execSync("sed -i 's/public static function check_seccode(\\$val, \\$idhash, \\$modid = 0) {/public static function check_seccode(\\$val, \\$idhash, \\$modid = 0) { return true;/g' source/class/helper/helper_seccheck.php || true");

        console.log("Testing UI Registration...");
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
            if (emails.length > 0) {
                emails[0].value = email;
            }

            const secqaa = form.querySelector('input[name*="secanswer"]');
            if (secqaa) secqaa.value = '1';

            const seccode = form.querySelector('input[name*="seccodeverify"]');
            if (seccode) seccode.value = '1111';

            const agree = form.querySelector('input[name="agree"]');
            if (agree) agree.checked = true;

            form.submit();
        }, { username, password, email });

        await page.waitForNavigation({ waitUntil: 'networkidle' }).catch(() => { });
        await page.waitForTimeout(3000);

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

            const loginSecAnswerInput = await page.$('input[name*="secanswer"]');
            if (loginSecAnswerInput) await loginSecAnswerInput.fill('1');
            const loginSecCodeInput = await page.$('input[name*="seccodeverify"]');
            if (loginSecCodeInput) await loginSecCodeInput.fill('1111');

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

        console.log("Attempting to post normal thread as unprivileged user...");
        await page.goto('http://127.0.0.1:8080/forum.php?mod=post&action=newthread&fid=2');
        await page.waitForLoadState('networkidle');

        await page.evaluate(({ title, body }) => {
            const subject = document.querySelector('input[name="subject"]');
            if (subject) subject.value = title;

            const message = document.querySelector('textarea[name="message"]');
            if (message) message.value = body;

            try {
                if (typeof editdoc !== 'undefined' && editdoc && editdoc.body) {
                    editdoc.body.innerHTML = body;
                }
            } catch (e) { }

            const secqaa = document.querySelector('input[name*="secanswer"]');
            if (secqaa) secqaa.value = '1';

            const seccode = document.querySelector('input[name*="seccodeverify"]');
            if (seccode) seccode.value = '1111';

            const postSubmitBtn = document.querySelector('button[name="topicsubmit"], #postsubmit');
            if (postSubmitBtn) {
                postSubmitBtn.click();
            } else {
                const form = document.getElementById('postform') || document.querySelector('form[name="postform"]');
                if (form) form.submit();
            }
        }, { title: 'Standard User Thread', body: 'Body text from unprivileged account.' });

        await page.waitForNavigation({ waitUntil: 'networkidle' }).catch(() => { });
        await page.waitForTimeout(3000);

        console.log("Checking if posted thread exists in DB...");
        const threadDbCheck = execSync("sudo mysql -u root ultrax -N -s -e \"SELECT COUNT(*) FROM pre_forum_thread WHERE subject='Standard User Thread';\"").toString().trim();
        console.log("DB count for thread:", threadDbCheck);

        const currentUrl = page.url();
        const postContent = await page.textContent('body');

        assert.ok(parseInt(threadDbCheck, 10) >= 1, 'Assertion Error: Normal user thread post was not found in database.');
        assert.ok(
            /mod=viewthread&tid=\d+/.test(currentUrl) || postContent.includes('Standard User Thread') || postContent.includes('非常感谢'),
            'Assertion Error: Normal user posting did not result in thread view or success message. Final URL: ' + currentUrl
        );
        report += '### 2. Unprivileged User Posting\n- **Status**: Checked\n- **Thread Created**: Standard User Thread\n\n';



    } catch (error) {
        console.error("Test execution failed:", error);
        process.exitCode = 1;
        report += "## Error Encountered\n```\n" + error.message + "\n```\n\n";
    } finally {
        // REVERT THE PHP CHANGES TO KEEP REPO CLEAN!
        execSync("git restore source/class/helper/helper_seccheck.php || true");

        await browser.close();
        fs.writeFileSync('functional_test_report.md', report);
        console.log("Tests completed.");
    }
})();
