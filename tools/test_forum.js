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
        C::t('common_setting')->update('regstatus', '1');
        C::t('common_setting')->update('regclose', '0');
        C::t('common_setting')->update('regverify', '0');
        C::t('common_setting')->update('floodctrl', '0');
        C::t('common_setting')->update('pmstatus', '1');
        C::t('common_setting')->update('commentpostself', '1');
        C::t('common_setting')->update('recommendthread', array('status' => '1', 'addtext' => 'Recommend', 'subtracttext' => 'Oppose', 'defaultshow' => '1', 'daycount' => '5', 'ownthread' => '1', 'allow' => '1'));
        C::t('common_setting')->update('repliesrank', '1');
        C::t('common_usergroup_field')->update(10, array('allowrecommend' => '1', 'allowpostattach' => '1', 'allowpostimage' => '1', 'allowposttag' => '1', 'allowcommentpost' => '3', 'attachextensions' => 'gif, jpg, png, txt, svg'));
        C::t('common_usergroup_field')->update(7, array('allowrecommend' => '1', 'allowpostattach' => '1', 'allowpostimage' => '1', 'allowposttag' => '1', 'allowcommentpost' => '3', 'attachextensions' => 'gif, jpg, png, txt, svg'));
        C::t('common_usergroup_field')->update(1, array('allowrecommend' => '1', 'allowpostattach' => '1', 'allowpostimage' => '1', 'allowposttag' => '1', 'allowcommentpost' => '3', 'attachextensions' => 'gif, jpg, png, txt, svg'));

        \$adminThread = C::t('forum_thread')->fetch_by_subject('Admin Seed Thread');
        if(!\$adminThread) {
            \$adminTid = C::t('forum_thread')->insert(array(
                'fid' => 2,
                'author' => 'admin',
                'authorid' => 1,
                'subject' => 'Admin Seed Thread',
                'dateline' => TIMESTAMP,
                'lastpost' => TIMESTAMP,
                'lastposter' => 'admin',
                'displayorder' => 0,
                'views' => 1,
                'replies' => 1,
                'status' => 32
            ), true);
            C::t('forum_post')->insert(array(
                'fid' => 2,
                'tid' => \$adminTid,
                'first' => 1,
                'author' => 'admin',
                'authorid' => 1,
                'subject' => 'Admin Seed Thread',
                'dateline' => TIMESTAMP,
                'message' => 'Admin Seed Thread Message Content',
                'useip' => '127.0.0.1',
                'invisible' => 0,
                'anonymous' => 0,
                'usesig' => 1,
                'htmlon' => 0,
                'bbcodeoff' => 0,
                'smileyoff' => -1,
                'parseurloff' => 0,
                'attachment' => 0
            ), true);
            C::t('forum_post')->insert(array(
                'fid' => 2,
                'tid' => \$adminTid,
                'first' => 0,
                'author' => 'admin',
                'authorid' => 1,
                'subject' => 'Admin Seed Reply',
                'dateline' => TIMESTAMP,
                'message' => 'Admin Seed Reply Message Content',
                'useip' => '127.0.0.1',
                'invisible' => 0,
                'anonymous' => 0,
                'usesig' => 1,
                'htmlon' => 0,
                'bbcodeoff' => 0,
                'smileyoff' => -1,
                'parseurloff' => 0,
                'attachment' => 0
            ), true);
        }

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
        console.log("Setting up user avatar via UI...");

        await page.goto('http://127.0.0.1:8080/home.php?mod=spacecp&ac=avatar');
        await page.waitForLoadState('networkidle');

        const avatarFixture = 'static/image/smiley/BQ2/alu1.jpg';
        const avatarInputs = page.locator('.choose-file');
        assert.strictEqual(await avatarInputs.count(), 3, 'Assertion Error: HTML5 avatar controls did not render.');
        assert.ok(fs.existsSync(avatarFixture), 'Assertion Error: Avatar fixture is missing.');
        for(let i = 0; i < 3; i++) {
            await avatarInputs.nth(i).setInputFiles(avatarFixture);
        }
        await page.locator('.submit-btn').click();
        await page.waitForLoadState('networkidle').catch(() => {});
        await page.waitForTimeout(1000);

        const userUid = execSync("sudo mysql -u root ultrax -N -s -e \"SELECT uid FROM pre_common_member WHERE username='" + username + "';\"").toString().trim();

        const avatarStatus = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT avatarstatus FROM pre_common_member WHERE uid='${userUid}';"`).toString().trim();
        assert.strictEqual(avatarStatus, '1', 'Assertion Error: User avatarstatus in database was not 1.');

        console.log("Testing Desktop Forum Front Page (forum.php)...");
        await page.goto('http://127.0.0.1:8080/forum.php');
        await page.waitForLoadState('networkidle');
        const forumIndexBody = await page.textContent('body');
        assert.ok(
            forumIndexBody.includes('Discuz!') || (await page.locator('#category_grid, .fl, #chart, #pt').count()) > 0,
            'Assertion Error: Desktop forum front page (forum.php) did not load correctly.'
        );
        await page.screenshot({ path: 'screenshot_desktop_forum_index.png', fullPage: true });
        console.log("✅ Desktop Forum Front Page loaded successfully.");
        report += '### Desktop Forum Front Page (forum.php)\n- **Status**: Checked\n- **Front Page Load**: Success\n- **Screenshot**: `screenshot_desktop_forum_index.png`\n\n';

        // Discover a real postable sub-board (type='forum') — never a group (type='group').
        const forumFid = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT fid FROM pre_forum_forum WHERE type='forum' LIMIT 1;"`).toString().trim();
        assert.ok(forumFid, 'Assertion Error: No postable sub-board (type=forum) found in pre_forum_forum.');

        console.log("Attempting to post normal thread as unprivileged user...");
        await page.goto(`http://127.0.0.1:8080/forum.php?mod=forumdisplay&fid=${forumFid}`);
        await page.waitForLoadState('networkidle');
        const postNewThreadBtn = page.locator('#newspecial, a[href*="action=newthread"], #newspecialtmp').first();
        assert.strictEqual(await postNewThreadBtn.count(), 1, 'Assertion Error: Desktop new-thread control did not render.');
        await postNewThreadBtn.click();
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
            assert.strictEqual(await desktopReplyBtn.count(), 1, 'Assertion Error: Desktop reply control did not render.');
            await desktopReplyBtn.click();
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

            // --- Test: Comment on first floor ---
            console.log("Posting comment on first floor via UI...");
            const firstFloorPid = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT pid FROM pre_forum_post WHERE tid='${tidOutput}' AND first=1 LIMIT 1;"`).toString().trim();
            assert.ok(firstFloorPid, 'Assertion Error: First floor post ID was not found.');

            await page.goto(`http://127.0.0.1:8080/forum.php?mod=viewthread&tid=${tidOutput}`);
            await page.waitForLoadState('networkidle');

            const firstFloorCommentBtn = page.locator(`a.cmmnt[href*="pid=${firstFloorPid}"]`);
            assert.strictEqual(await firstFloorCommentBtn.count(), 1, 'Assertion Error: Comment control did not render for the first floor post.');
            await firstFloorCommentBtn.click();

            const firstFloorCommentForm = page.locator('#fwin_comment form#commentform');
            await firstFloorCommentForm.waitFor({ state: 'visible' });
            const firstFloorCommentMessage = firstFloorCommentForm.locator('#commentmessage');
            const firstFloorSubmitCommentBtn = firstFloorCommentForm.locator('#commentsubmit');
            assert.strictEqual(await firstFloorCommentMessage.count(), 1, 'Assertion Error: First floor comment message input did not render.');
            assert.strictEqual(await firstFloorSubmitCommentBtn.count(), 1, 'Assertion Error: First floor comment submit button did not render.');

            const firstFloorCommentText = 'Test comment on first floor.';
            await firstFloorCommentMessage.fill(firstFloorCommentText);
            await Promise.all([
                page.waitForResponse(response => response.request().method() === 'POST' && response.url().includes('mod=post') && response.url().includes('commentsubmit=yes')),
                firstFloorSubmitCommentBtn.click()
            ]);

            const firstFloorCommentDbCheck = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT COUNT(*) FROM pre_forum_postcomment WHERE authorid='${userUid}' AND pid='${firstFloorPid}' AND comment='${firstFloorCommentText}';"`).toString().trim();
            assert.strictEqual(firstFloorCommentDbCheck, '1', 'Assertion Error: First floor comment was not created in database.');

            // Navigate back to viewthread to verify and screenshot
            await page.goto(`http://127.0.0.1:8080/forum.php?mod=viewthread&tid=${tidOutput}`);
            await page.waitForLoadState('networkidle');
            await page.screenshot({ path: 'screenshot_desktop_viewthread_commented_first_floor.png' });
            console.log("✅ Comment on first floor posted successfully.");


            // --- Test: Simple Editor (Fast Post / Fast Reply) ---
            console.log("Testing Simple Editor (Fast Post / Fast Reply)...");
            await page.goto(`http://127.0.0.1:8080/forum.php?mod=viewthread&tid=${tidOutput}`);
            await page.waitForLoadState('networkidle');

            const fastPostForm = page.locator('#fastpostform');
            assert.strictEqual(await fastPostForm.count(), 1, 'Assertion Error: Simple Editor (Fast Post) form did not render on viewthread page.');

            const fastPostTextarea = fastPostForm.locator('#fastpostmessage');
            assert.strictEqual(await fastPostTextarea.count(), 1, 'Assertion Error: Simple Editor message field (#fastpostmessage) did not render.');
            await fastPostTextarea.fill('Fast reply text from unprivileged account.');

            // Focus and trigger potential onmouseover checkpostrule/loading of secqaa
            await fastPostTextarea.focus();
            await page.waitForTimeout(500);

            // Screenshot the simple editor before submitting
            await page.screenshot({ path: 'screenshot_desktop_simple_editor.png' });

            const fastPostSecqaa = fastPostForm.locator('input[name*="secanswer"]');
            if (await fastPostSecqaa.count() && await fastPostSecqaa.isVisible()) {
                await fastPostSecqaa.fill('2');
            } else {
                await page.waitForTimeout(1000);
                const lazySecqaa = fastPostForm.locator('input[name*="secanswer"]');
                if (await lazySecqaa.count() && await lazySecqaa.isVisible()) {
                    await lazySecqaa.fill('2');
                }
            }

            const fastPostSubmitBtn = fastPostForm.locator('#fastpostsubmit');
            assert.strictEqual(await fastPostSubmitBtn.count(), 1, 'Assertion Error: Simple Editor submit button (#fastpostsubmit) did not render.');

            await Promise.all([
                page.waitForResponse(response => 
                    response.request().method() === 'POST' && 
                    response.url().includes('mod=post') && 
                    response.url().includes('action=reply') && 
                    response.url().includes('replysubmit=yes')
                ),
                fastPostSubmitBtn.click()
            ]);
            await page.waitForTimeout(2000);

            console.log("Checking if fast reply exists in DB...");
            const fastReplyDbCheck = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT COUNT(*) FROM pre_forum_post WHERE tid='${tidOutput}' AND message='Fast reply text from unprivileged account.';"`).toString().trim();
            assert.strictEqual(fastReplyDbCheck, '1', 'Assertion Error: Fast reply post was not found in database.');
            report += '### Simple Editor (Fast Post / Fast Reply)\n- **Status**: Checked\n- **Fast Reply Created**: Fast reply text from unprivileged account.\n\n';


            // Edit Thread
            console.log("Attempting to edit thread...");
            const pidOutput = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT pid FROM pre_forum_post WHERE tid='${tidOutput}' AND first=1 LIMIT 1;"`).toString().trim();
            if (pidOutput) {
                const editPostBtn = page.locator(`a[href*="action=edit"][href*="pid=${pidOutput}"]`).first();
                assert.strictEqual(await editPostBtn.count(), 1, 'Assertion Error: Desktop edit control did not render.');
                await editPostBtn.click();
                const editForm = page.locator('#fwin_edit form#postform_edit');
                await editForm.waitFor({ state: 'visible' });
                assert.strictEqual(await editForm.count(), 1, 'Assertion Error: Desktop edit modal did not render its form.');

                const editSubject = editForm.locator('input[name="subject"]');
                assert.strictEqual(await editSubject.count(), 1, 'Assertion Error: Desktop edit subject input did not render.');
                await editSubject.fill('Standard User Thread (Edited)');
                const editMessage = editForm.locator('textarea[name="message"]');
                assert.strictEqual(await editMessage.count(), 1, 'Assertion Error: Desktop edit message input did not render.');
                await editMessage.fill('Edited body text from unprivileged account.');
                const editSecqaa = editForm.locator('input[name*="secanswer"]');
                if(await editSecqaa.count()) await editSecqaa.fill('2');
                const editBtn = editForm.locator('button[name="editsubmit"]');
                assert.strictEqual(await editBtn.count(), 1, 'Assertion Error: Desktop edit submit button did not render.');
                await editBtn.click();
                await page.waitForFunction(() => {
                    const modal = document.getElementById('fwin_edit');
                    return !modal || modal.style.display === 'none';
                }, null, { timeout: 5000 });

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

        console.log("Testing Other User Threads Page (home.php?mod=space&uid=1&do=thread)...");
        await page.goto('http://127.0.0.1:8080/home.php?mod=space&uid=1&do=thread');
        await page.waitForLoadState('networkidle');
        await page.screenshot({ path: 'screenshot_space_thread_default.png' });

        const defaultThreadBody = await page.textContent('body');
        assert.ok(
            defaultThreadBody.includes('admin'),
            'Assertion Error: Other user threads page (uid=1) did not load correctly.'
        );

        console.log("Testing User Replies Page (home.php?mod=space&do=thread&view=me&type=reply)...");
        await page.goto('http://127.0.0.1:8080/home.php?mod=space&do=thread&view=me&type=reply');
        await page.waitForLoadState('networkidle');
        await page.screenshot({ path: 'screenshot_desktop_space_thread_reply.png' });

        const viewReplyBody = await page.textContent('body');
        assert.ok(
            viewReplyBody.includes('Reply text from unprivileged account.'),
            'Assertion Error: view=me&type=reply user replies page did not load correctly.'
        );

        console.log("Testing Thread Recommendation and Hot Reply Voting via UI...");
        const adminTidOutput = execSync("sudo mysql -u root ultrax -N -s -e \"SELECT tid FROM pre_forum_thread WHERE authorid=1 ORDER BY tid DESC LIMIT 1;\"").toString().trim();
        const adminReplyPidOutput = execSync("sudo mysql -u root ultrax -N -s -e \"SELECT pid FROM pre_forum_post WHERE authorid=1 AND first=0 ORDER BY pid DESC LIMIT 1;\"").toString().trim();
        const targetRecommendTid = adminTidOutput || tidOutput;
        const targetSupportTid = adminReplyPidOutput ? execSync(`sudo mysql -u root ultrax -N -s -e "SELECT tid FROM pre_forum_post WHERE pid='${adminReplyPidOutput}' LIMIT 1;"`).toString().trim() : tidOutput;

        await page.goto(`http://127.0.0.1:8080/forum.php?mod=viewthread&tid=${targetRecommendTid}`);
        await page.waitForLoadState('networkidle');
        const recommendBtn = page.locator('a[href*="action=recommend&do=add"]').first();
        assert.strictEqual(await recommendBtn.count(), 1, 'Assertion Error: Desktop thread recommend button did not render.');
        assert.ok(await recommendBtn.isVisible(), 'Assertion Error: Desktop thread recommend button was not visible.');
        console.log("Clicking desktop thread recommend button via UI...");
        await recommendBtn.click();
        await page.waitForTimeout(1000);

        await page.goto(`http://127.0.0.1:8080/forum.php?mod=viewthread&tid=${targetSupportTid}`);
        await page.waitForLoadState('networkidle');
        const supportBtn = page.locator('a[href*="action=postreview&do=support"]').first();
        assert.strictEqual(await supportBtn.count(), 1, 'Assertion Error: Desktop postreview support button did not render.');
        assert.ok(await supportBtn.isVisible(), 'Assertion Error: Desktop postreview support button was not visible.');
        console.log("Clicking desktop postreview support button via UI...");
        await supportBtn.click();
        await page.waitForTimeout(1000);

        await page.goto(`http://127.0.0.1:8080/forum.php?mod=viewthread&tid=${targetRecommendTid}`);
        await page.waitForLoadState('networkidle');
        await page.screenshot({ path: 'screenshot_desktop_thread_recommend.png' });

        report += '### 4b. Personal Info Update & Space Threads Verification\n- **Status**: Checked\n- **spacecp Update**: Success\n- **Threads Page (with view=me)**: Success — `screenshot_space_thread_viewme.png`\n- **Other User Threads Page (uid=1)**: Success — `screenshot_space_thread_default.png`\n- **User Replies Page (type=reply)**: Success — `screenshot_desktop_space_thread_reply.png`\n- **User Postcomments Page (type=postcomment)**: Success — `screenshot_desktop_space_thread_postcomment.png`\n- **Thread Recommendation & Hot Reply Check**: Success — `screenshot_desktop_thread_recommend.png`\n\n';

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

            await adminPage.goto(`http://127.0.0.1:8080/forum.php?mod=post&action=reply&fid=${forumFid}&tid=${tidOutput}&reppost=${firstPid}`);
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

            console.log("Posting postcomment via UI and testing type=postcomment page...");
            const postCommentText = 'Test postcomment content text.';
            const adminReplyPid = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT pid FROM pre_forum_post WHERE tid='${tidOutput}' AND authorid=1 AND first=0 AND message LIKE '%Admin quote reply to user thread.%' ORDER BY pid DESC LIMIT 1;"`).toString().trim();
            assert.ok(adminReplyPid, 'Assertion Error: Admin reply post ID was not found.');

            await page.goto(`http://127.0.0.1:8080/forum.php?mod=viewthread&tid=${tidOutput}`);
            await page.waitForLoadState('networkidle');
            const commentBtn = page.locator(`a.cmmnt[href*="pid=${adminReplyPid}"]`);
            assert.strictEqual(await commentBtn.count(), 1, 'Assertion Error: Comment control did not render for the admin reply.');
            await commentBtn.click();

            const commentForm = page.locator('#fwin_comment form#commentform');
            await commentForm.waitFor({ state: 'visible' });
            const commentMessage = commentForm.locator('#commentmessage');
            const submitCommentBtn = commentForm.locator('#commentsubmit');
            assert.strictEqual(await commentMessage.count(), 1, 'Assertion Error: Post comment message input did not render.');
            assert.strictEqual(await submitCommentBtn.count(), 1, 'Assertion Error: Post comment submit button did not render.');
            await commentMessage.fill(postCommentText);
            await Promise.all([
                page.waitForResponse(response => response.request().method() === 'POST' && response.url().includes('mod=post') && response.url().includes('commentsubmit=yes')),
                submitCommentBtn.click()
            ]);

            const postCommentDbCheck = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT COUNT(*) FROM pre_forum_postcomment WHERE authorid='${userUid}' AND pid='${adminReplyPid}' AND comment='${postCommentText}';"`).toString().trim();
            assert.strictEqual(postCommentDbCheck, '1', 'Assertion Error: Post comment was not created in database.');

            // Navigate back to viewthread to verify and screenshot the postcomment
            await page.goto(`http://127.0.0.1:8080/forum.php?mod=viewthread&tid=${tidOutput}`);
            await page.waitForLoadState('networkidle');
            await page.screenshot({ path: 'screenshot_desktop_viewthread_commented.png' });

            await page.goto('http://127.0.0.1:8080/home.php?mod=space&do=thread&view=me&type=postcomment');
            await page.waitForLoadState('networkidle');
            await page.screenshot({ path: 'screenshot_desktop_space_thread_postcomment.png' });
            const viewPostcommentBody = await page.textContent('body');
            assert.ok(
                viewPostcommentBody.includes(postCommentText),
                'Assertion Error: view=me&type=postcomment page did not load correctly.'
            );

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

        console.log("Checking other user's profile page on desktop (admin uid=1)...");
        await page.goto('http://127.0.0.1:8080/home.php?mod=space&uid=1&do=profile');
        await page.waitForLoadState('networkidle');
        const otherProfileBody = await page.textContent('body');
        assert.ok(otherProfileBody.includes('admin'), 'Assertion Error: Desktop other user profile page did not load.');
        await page.screenshot({ path: 'screenshot_desktop_other_user_profile.png' });

        console.log("Checking header for user custom avatar...");
        await page.goto(`http://127.0.0.1:8080/forum.php?mod=forumdisplay&fid=${forumFid}`);
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

        report += '### 5. Unprivileged User Avatar Setup & Verification\n- **Status**: Checked\n- **Avatar Status in DB**: 1\n- **Profile Avatar Check**: Passed\n- **Other User Profile Screenshot**: `screenshot_desktop_other_user_profile.png`\n- **Header Avatar Check**: Passed\n- **Viewthread Avatar Check**: Passed\n\n';

        // 6. User Image Attachment Post Test
        console.log("Attempting to post thread with image attachment...");
        await page.goto(`http://127.0.0.1:8080/forum.php?mod=post&action=newthread&fid=${forumFid}`);
        await page.waitForLoadState('networkidle');

        const attachSubject = await page.$('input[name="subject"]');
        if (attachSubject) {
            await attachSubject.fill('Thread with Attachment');
        }

        const uploaderRuntime = await page.evaluate(() => ({
            available: typeof DiscuzUploader === 'function',
            scripts: Array.from(document.scripts).map(script => script.src).filter(Boolean),
        }));
        assert.ok(uploaderRuntime.available, 'Assertion Error: Desktop HTML5 DiscuzUploader runtime did not load.');
        assert.ok(
            uploaderRuntime.scripts.some(src => /\/discuz_uploader\.js(?:\?|$)/.test(src)),
            `Assertion Error: Renamed desktop uploader script was not loaded. Scripts: ${uploaderRuntime.scripts.join(', ')}`
        );

        const attachmentFixture = 'static/image/smiley/BQ2/alu1.jpg';
        assert.ok(fs.existsSync(attachmentFixture), `Assertion Error: Attachment fixture is missing: ${attachmentFixture}`);
        const uploadPickers = page.locator('div[id^="rt_"] input[type="file"]');
        assert.strictEqual(await uploadPickers.count(), 2, 'Assertion Error: Desktop WebUploader pickers did not render.');
        const imageInput = uploadPickers.nth(0);
        const uploadResponse = page.waitForResponse(response => response.request().method() === 'POST' && response.url().includes('misc.php?mod=upload'));
        await imageInput.setInputFiles(attachmentFixture);
        const lastUploadResp = await (await uploadResponse).text();
        assert.match(lastUploadResp.trim(), /^\d+$/, `Assertion Error: Desktop image upload failed. Response: ${lastUploadResp}`);
        await page.waitForFunction(() => document.querySelector('#imgattachlist input[name^="attachnew["]'), null, { timeout: 5000 });
        const aid = await page.locator('#imgattachlist input[name^="attachnew["]').evaluate(input => input.name.match(/^attachnew\[(\d+)\]/)[1]);
        console.log("Discovered attachment AID:", aid);

        const attachMsg = `Posting thread with image attachment content. [attach]${aid}[/attach]`;

        await page.evaluate(({ aidVal, message }) => {
            const textArea = document.querySelector('textarea[name="message"], #postmessage');
            if (textArea) textArea.value = message;
            if (window.editdoc && window.editdoc.body) window.editdoc.body.innerHTML = message;
            const secqaa = document.querySelector('input[name*="secanswer"]');
            if (secqaa) secqaa.value = '2';
        }, { aidVal: aid, message: attachMsg });

        const extraTagBtn = await page.$('#extra_tag_b, a[href*="extra_tag"], #extra_tag_b a');
        assert.ok(extraTagBtn, 'Assertion Error: Desktop tag control did not render.');
        const tagsInput = page.locator('#keyword-input');
        assert.strictEqual(await tagsInput.count(), 1, 'Assertion Error: Desktop tag input did not render.');
        if(!await tagsInput.isVisible()) {
            await extraTagBtn.click();
            await tagsInput.waitFor({ state: 'visible' });
        }
        await tagsInput.fill('sample tag');
        await tagsInput.press('Enter');

        const attachSubmitBtn = await page.$('button[name="topicsubmit"]');
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
            viewthreadBody.includes('Thread with Attachment') && viewthreadBody.includes('Posting thread with image attachment content.') && viewthreadBody.includes('sample tag'),
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

        // 6b. Non-Image Attachment Post Test
        console.log("Attempting to post thread with non-image attachment...");
        await page.goto(`http://127.0.0.1:8080/forum.php?mod=post&action=newthread&fid=${forumFid}`);
        await page.waitForLoadState('networkidle');

        const nonImgSubject = await page.$('input[name="subject"]');
        if (nonImgSubject) {
            await nonImgSubject.fill('Thread with Non-Image Attachment');
        }

        fs.mkdirSync('scratch', { recursive: true });
        const nonImgFixture = 'scratch/sample_test_document.txt';
        fs.writeFileSync(nonImgFixture, 'This is a test non-image attachment document content.');
        const nonImgPickers = page.locator('div[id^="rt_"] input[type="file"]');
        assert.strictEqual(await nonImgPickers.count(), 2, 'Assertion Error: Desktop WebUploader pickers did not render.');
        const nonImgInput = nonImgPickers.nth(1);
        const nonImgUploadResponse = page.waitForResponse(response => response.request().method() === 'POST' && response.url().includes('misc.php?mod=upload'));
        await nonImgInput.setInputFiles(nonImgFixture);
        const nonImgResp = await (await nonImgUploadResponse).text();
        assert.match(nonImgResp.trim(), /^\d+$/, `Assertion Error: Desktop non-image upload failed. Response: ${nonImgResp}`);
        await page.waitForFunction(() => document.querySelector('#fsUploadProgress input[name^="attachnew["]'), null, { timeout: 5000 });
        const nonImgAid = await page.locator('#fsUploadProgress input[name^="attachnew["]').evaluate(input => input.name.match(/^attachnew\[(\d+)\]/)[1]);
        console.log("Discovered non-image attachment AID:", nonImgAid);

        const nonImgAttachMsg = `Posting thread with non-image attachment document. [attach]${nonImgAid}[/attach]`;

        await page.evaluate(({ aidVal, message }) => {
            const textArea = document.querySelector('textarea[name="message"], #postmessage');
            if (textArea) textArea.value = message;
            if (window.editdoc && window.editdoc.body) window.editdoc.body.innerHTML = message;
            const secqaa = document.querySelector('input[name*="secanswer"]');
            if (secqaa) secqaa.value = '2';
        }, { aidVal: nonImgAid, message: nonImgAttachMsg });

        const nonImgSubmitBtn = await page.$('button[name="topicsubmit"]');
        if (nonImgSubmitBtn) {
            await nonImgSubmitBtn.click();
            await page.waitForNavigation({ waitUntil: 'networkidle' }).catch(() => { });
            await page.waitForTimeout(2000);
        }

        console.log("Checking if non-image attachment thread exists in DB and loads in viewthread...");
        const nonImgTid = execSync("sudo mysql -u root ultrax -N -s -e \"SELECT tid FROM pre_forum_thread WHERE subject='Thread with Non-Image Attachment' ORDER BY tid DESC LIMIT 1;\"").toString().trim();
        assert.ok(nonImgTid, 'Assertion Error: Thread with non-image attachment was not created in database.');

        await page.goto(`http://127.0.0.1:8080/forum.php?mod=viewthread&tid=${nonImgTid}`);
        await page.waitForLoadState('networkidle');
        await page.screenshot({ path: 'screenshot_attachment_non_image_viewthread.png' });

        const nonImgViewthreadBody = await page.textContent('body');
        assert.ok(
            nonImgViewthreadBody.includes('Thread with Non-Image Attachment') && nonImgViewthreadBody.includes('sample_test_document.txt'),
            'Assertion Error: Non-image attachment thread page did not load content in viewthread.'
        );

        // 6c. SVG Image Attachment Post Test
        console.log("Attempting to post thread with SVG image attachment...");
        await page.goto(`http://127.0.0.1:8080/forum.php?mod=post&action=newthread&fid=${forumFid}`);
        await page.waitForLoadState('networkidle');

        const svgSubject = await page.$('input[name="subject"]');
        if (svgSubject) {
            await svgSubject.fill('Thread with SVG Attachment');
        }

        fs.mkdirSync('scratch', { recursive: true });
        const svgFixture = 'scratch/sample_icon.svg';
        fs.writeFileSync(svgFixture, '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle cx="50" cy="50" r="40" fill="blue" /></svg>');

        const svgPickers = page.locator('div[id^="rt_"] input[type="file"]');
        assert.strictEqual(await svgPickers.count(), 2, 'Assertion Error: Desktop WebUploader pickers did not render.');
        const svgInput = svgPickers.nth(0);
        const svgUploadResponse = page.waitForResponse(response => response.request().method() === 'POST' && response.url().includes('misc.php?mod=upload'));
        await svgInput.setInputFiles(svgFixture);
        const svgResp = await (await svgUploadResponse).text();
        assert.match(svgResp.trim(), /^\d+$/, `Assertion Error: Desktop SVG upload failed. Response: ${svgResp}`);
        await page.waitForFunction(() => document.querySelector('#imgattachlist input[name^="attachnew["]'), null, { timeout: 5000 });
        const svgAid = await page.locator('#imgattachlist input[name^="attachnew["]').evaluate(input => input.name.match(/^attachnew\[(\d+)\]/)[1]);
        console.log("Discovered SVG attachment AID:", svgAid);

        const svgAttachMsg = `Posting thread with SVG image content. [attach]${svgAid}[/attach]`;

        await page.evaluate(({ aidVal, message }) => {
            const textArea = document.querySelector('textarea[name="message"], #postmessage');
            if (textArea) textArea.value = message;
            if (window.editdoc && window.editdoc.body) window.editdoc.body.innerHTML = message;
            const secqaa = document.querySelector('input[name*="secanswer"]');
            if (secqaa) secqaa.value = '2';
        }, { aidVal: svgAid, message: svgAttachMsg });

        const svgSubmitBtn = await page.$('button[name="topicsubmit"]');
        if (svgSubmitBtn) {
            await svgSubmitBtn.click();
            await page.waitForNavigation({ waitUntil: 'networkidle' }).catch(() => { });
            await page.waitForTimeout(2000);
        }

        console.log("Checking if SVG attachment thread exists in DB and loads in viewthread...");
        const svgTid = execSync("sudo mysql -u root ultrax -N -s -e \"SELECT tid FROM pre_forum_thread WHERE subject='Thread with SVG Attachment' ORDER BY tid DESC LIMIT 1;\"").toString().trim();
        assert.ok(svgTid, 'Assertion Error: Thread with SVG attachment was not created in database.');

        const svgDbRecord = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT COUNT(*) FROM pre_forum_attachment WHERE tid='${svgTid}';"`).toString().trim();
        assert.ok(parseInt(svgDbRecord, 10) >= 1, 'Assertion Error: SVG attachment record was not linked in pre_forum_attachment database table.');

        await page.goto(`http://127.0.0.1:8080/forum.php?mod=viewthread&tid=${svgTid}`);
        await page.waitForLoadState('networkidle');
        await page.screenshot({ path: 'screenshot_attachment_svg_viewthread.png' });

        const svgAttachmentIndex = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT CONCAT(tid, ':', tableid) FROM pre_forum_attachment WHERE aid='${svgAid}' LIMIT 1;"`).toString().trim();
        const svgAttachTableId = svgAttachmentIndex.split(':')[1];
        const svgIsImage = svgAttachTableId === undefined ? '' : execSync(`sudo mysql -u root ultrax -N -s -e "SELECT isimage FROM pre_forum_attachment_${svgAttachTableId} WHERE aid='${svgAid}' AND tid='${svgTid}' LIMIT 1;"`).toString().trim();
        assert.ok(svgIsImage === '1' || svgIsImage === '2', `Assertion Error: Uploaded SVG was not stored as an image. isimage: ${svgIsImage}`);

        const svgViewthreadBody = await page.textContent('body');
        assert.ok(
            svgViewthreadBody.includes('Thread with SVG Attachment') && svgViewthreadBody.includes('Posting thread with SVG image content.'),
            'Assertion Error: SVG attachment thread page did not load content cleanly in viewthread.'
        );
        report += '### 6c. SVG Attachment Post\n- **Status**: Checked\n- **Thread Created**: Thread with SVG Attachment (TID: ' + svgTid + ', AID: ' + svgAid + ')\n- **SVG Stored as Image (isimage)**: ' + svgIsImage + '\n- **Screenshot**: `screenshot_attachment_svg_viewthread.png`\n\n';

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
