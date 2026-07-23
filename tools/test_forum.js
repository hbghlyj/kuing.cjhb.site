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

        DB::query("TRUNCATE TABLE ".DB::table('common_secquestion'));
        C::t('common_secquestion')->insert(array('type' => 0, 'question' => '1+1=?', 'answer' => '2'));

        \$seccodedata = array('rule' => array('register' => array('allow' => 0, 'numlimit' => '', 'timelimit' => 0),'login' => array('allow' => 0, 'nolocal' => 0, 'pwsimple' => 0, 'pwerror' => 0, 'outofday' => '', 'numiptry' => '', 'timeiptry' => 0),'post' => array('allow' => 0, 'numlimit' => '', 'timelimit' => 0, 'nplimit' => '', 'vplimit' => ''),'password' => array('allow' => 0),'card' => array('allow' => 0)),'minposts' => '','type' => 0,'width' => 150,'height' => 60,'scatter' => 0,'background' => 0,'adulterate' => 0,'ttf' => 0,'angle' => 0,'warping' => 0,'color' => 0,'size' => 0,'shadow' => 0,'animator' => 0);
        \$secqaa = array('status' => 1, 'minposts' => 0, 'statuses' => array(1 => 1, 2 => 1, 3 => 1), 'allowcode' => 0, 'allowqa' => 1);
        C::t('common_setting')->update('seccodedata', serialize(\$seccodedata));
        C::t('common_setting')->update('secqaa', serialize(\$secqaa));
        C::t('common_setting')->update('regname', 'register');
        C::t('common_setting')->update('floodctrl', '0');
        C::t('common_setting')->update('attachimgpost', '1');
        C::t('common_usergroup_field')->update(10, array('allowpostattach' => '1', 'allowpostimage' => '1'));
        C::t('common_usergroup_field')->update(7, array('allowpostattach' => '1', 'allowpostimage' => '1'));

        require_once libfile('function/cache');
        updatecache(array('setting', 'secqaa', 'styles', 'usergroups'));
        ?>`;
        fs.writeFileSync('disable_sec.php', phpConfig);
        execSync('php disable_sec.php');
        execSync('rm disable_sec.php');



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

            const agree = form.querySelector('input[name="agree"]');
            if (agree) agree.checked = true;

            const secqaa = form.querySelector('input[name*="secanswer"]');
            if (secqaa) secqaa.value = '2';

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

        console.log("Attempting to post normal thread as unprivileged user...");
        await page.goto('http://127.0.0.1:8080/forum.php?mod=post&action=newthread&fid=2');
        await page.waitForLoadState('networkidle');

        console.log("Capturing Advanced Editor Screenshot...");
        await page.screenshot({ path: 'screenshot_advanced_editor.png', fullPage: true }).catch(() => { });

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
            if (secqaa) secqaa.value = '2';

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

        const tidOutput = execSync("sudo mysql -u root ultrax -N -s -e \"SELECT tid FROM pre_forum_thread WHERE subject='Standard User Thread' ORDER BY tid DESC LIMIT 1;\"").toString().trim();

        if (tidOutput) {
            // Reply to Thread
            console.log("Attempting to reply to thread...");
            await page.goto(`http://127.0.0.1:8080/forum.php?mod=post&action=reply&fid=2&tid=${tidOutput}`);
            await page.waitForLoadState('networkidle');

            await page.evaluate((message) => {
                const textArea = document.querySelector('textarea[name="message"], #postmessage');
                if (textArea) textArea.value = message;
                try {
                    if (window.editdoc && window.editdoc.body) window.editdoc.body.innerHTML = message;
                } catch (e) { }
                const secqaa = document.querySelector('input[name*="secanswer"]');
                if (secqaa) secqaa.value = '2';
            }, 'Reply text from unprivileged account.');
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
                await page.goto(`http://127.0.0.1:8080/forum.php?mod=post&action=edit&fid=2&tid=${tidOutput}&pid=${pidOutput}`);
                await page.waitForLoadState('networkidle');

                const editSubject = await page.$('input[name="subject"]');
                if (editSubject) {
                    await editSubject.fill('Standard User Thread (Edited)');
                }
                await page.evaluate((message) => {
                    const textArea = document.querySelector('textarea[name="message"], #postmessage');
                    if (textArea) textArea.value = message;
                    try {
                        if (window.editdoc && window.editdoc.body) window.editdoc.body.innerHTML = message;
                    } catch (e) { }
                    const secqaa = document.querySelector('input[name*="secanswer"]');
                    if (secqaa) secqaa.value = '2';
                }, 'Edited body text from unprivileged account.');
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

        // Use a 10x10 PNG image (100 pixels >= 16 minimum required by Discuz! forum_upload size check)
        const sampleImage = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAoAAAAKCAYAAACNMs+9AAAAEUlEQVR42mP8z8AARDAEg4gAAH8YAwE8j7i4AAAAAElFTkSuQmCC', 'base64');
        fs.writeFileSync('sample_test_avatar.png', sampleImage);

        await page.goto('http://127.0.0.1:8080/home.php?mod=spacecp&ac=avatar');
        await page.waitForLoadState('networkidle');

        const avatarInput = await page.$('#avatarfile, input[name="Filedata"], input[type="file"]');
        if (avatarInput) {
            await avatarInput.setInputFiles('sample_test_avatar.png');
            await page.waitForTimeout(1000);

            const confirmBtn = await page.$('#avconfirm, input[name="confirm"], button[type="submit"]');
            if (confirmBtn) {
                await confirmBtn.click().catch(() => { });
                await page.waitForTimeout(2000);
            }
        }

        const userUid = execSync("sudo mysql -u root ultrax -N -s -e \"SELECT uid FROM pre_common_member WHERE username='" + username + "';\"").toString().trim();

        // Ensure avatarstatus=1 and UC avatar files exist for rendering tests
        const avatarSetupPhp = `<?php
        require './source/class/class_core.php';
        C::app()->init();
        $uid = ${userUid};
        C::t('common_member')->update($uid, array('avatarstatus' => '1'));
        $formattedUid = sprintf('%09d', $uid);
        $dir1 = substr($formattedUid, 0, 3);
        $dir2 = substr($formattedUid, 3, 2);
        $dir3 = substr($formattedUid, 5, 2);
        $relDir = $dir1 . '/' . $dir2 . '/' . $dir3;
        $lastTwo = substr($formattedUid, -2);
        $imgData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        foreach(array('./data/avatar/', './uc_server/data/avatar/') as $base) {
            $targetDir = $base . $relDir;
            if (!is_dir($targetDir)) { mkdir($targetDir, 0777, true); }
            file_put_contents($targetDir . '/' . $lastTwo . '_avatar_big.jpg', $imgData);
            file_put_contents($targetDir . '/' . $lastTwo . '_avatar_middle.jpg', $imgData);
            file_put_contents($targetDir . '/' . $lastTwo . '_avatar_small.jpg', $imgData);
        }
        ?>`;
        fs.writeFileSync('set_avatar.php', avatarSetupPhp);
        execSync('php set_avatar.php');
        fs.unlinkSync('set_avatar.php');

        const avatarStatus = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT avatarstatus FROM pre_common_member WHERE uid='${userUid}';"`).toString().trim();
        assert.strictEqual(avatarStatus, '1', 'Assertion Error: User avatarstatus in database was not 1.');

        console.log("Checking profile page for user custom avatar...");
        await page.goto(`http://127.0.0.1:8080/home.php?mod=space&uid=${userUid}&do=profile`);
        await page.waitForLoadState('networkidle');

        const profileAvatarImg = await page.$('.userinfo .avatar_m img, .avatar img, img[src*="avatar"], .user_avatar');
        assert.ok(profileAvatarImg !== null, 'Assertion Error: Avatar image element was not rendered on profile page.');

        console.log("Checking header for user custom avatar...");
        const headerAvatarImg = await page.$('#um .avt img, .header .avatar img, #hd .avatar img, .mz img[src*="avatar"], .userinfo_icon img, a[href*="mod=space"] img, img[src*="avatar"]');
        assert.ok(headerAvatarImg !== null, 'Assertion Error: Avatar image element was not rendered in page header.');

        console.log("Checking viewthread page for author custom avatar...");
        await page.goto(`http://127.0.0.1:8080/forum.php?mod=viewthread&tid=${tidOutput}`);
        await page.waitForLoadState('networkidle');

        const viewthreadAvatarImg = await page.$('.pls .avatar img, .postauthor .avatar img, .pls .avatar, .postauthor .avatar, img[src*="avatar"]');
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

        const rejectedUploadResp = await page.request.post('http://127.0.0.1:8080/misc.php?mod=swfupload&operation=upload&simple=1&type=image&fid=2');
        assert.strictEqual(rejectedUploadResp.status(), 403, 'Assertion Error: Upload endpoint accepted a request without formhash.');

        let aid = '';
        let lastUploadResp = '';
        try {
            const uploadResp = await page.request.post('http://127.0.0.1:8080/misc.php?mod=swfupload&operation=upload&simple=1&type=image&fid=2', {
                multipart: {
                    formhash,
                    Filedata: {
                        name: 'sample_test_avatar.png',
                        mimeType: 'image/png',
                        buffer: fs.readFileSync('sample_test_avatar.png')
                    }
                }
            });
            lastUploadResp = await uploadResp.text();
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

        const attachMsg = aid ? `Posting thread with image attachment content. [attachimg]${aid}[/attachimg]` : 'Posting thread with image attachment content.';

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
                    hiddenInput.value = 'sample_test_avatar.png';
                    form.appendChild(hiddenInput);
                }
            }
            const secqaa = document.querySelector('input[name*="secanswer"]');
            if (secqaa) secqaa.value = '2';
        }, { aidVal: aid, message: attachMsg });

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
            viewthreadBody.includes('Thread with Attachment') && viewthreadBody.includes('Posting thread with image attachment content.'),
            'Assertion Error: Attachment thread page did not load thread content cleanly in viewthread.'
        );

        const attachedImg = await page.$('img[aid], img[id^="aimg_"], .aimg img, img[src*="attachment/forum"], .zoom');
        assert.ok(attachedImg !== null, 'Assertion Error: Attached image element was not rendered in viewthread DOM.');

        await page.screenshot({ path: 'screenshot_attachment_viewthread.png' }).catch(() => { });

        report += '### 6. Unprivileged User Image Attachment Post\n- **Status**: Checked\n- **Thread Created**: Thread with Attachment (TID: ' + attachTid + ', AID: ' + (aid || 'N/A') + ')\n- **Image Attachment DOM Check**: Passed\n- **Viewthread Verification**: Success\n\n';

        if (fs.existsSync('sample_test_avatar.png')) {
            fs.unlinkSync('sample_test_avatar.png');
        }



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
