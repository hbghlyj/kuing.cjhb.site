const { chromium } = require('playwright');
const crypto = require('crypto');
const fs = require('fs');
const assert = require('assert');
const { execSync } = require('child_process');

(async () => {
    const browser = await chromium.launch();
    const context = await browser.newContext({
        viewport: { width: 390, height: 844 },
        locale: 'en-US',
    });
    const cookieSalt = crypto.createHash('md5').update('/|').digest('hex').slice(0, 4);
    await context.addCookies([
        { name: `discuz_${cookieSalt}_mobile`, value: '2', url: 'http://127.0.0.1:8080' },
    ]);
    const page = await context.newPage();
    const browserErrors = [];
    const trackBrowserErrors = (targetPage, label) => {
        targetPage.on('pageerror', error => {
            browserErrors.push(`${label} page error: ${error.message}`);
        });
        targetPage.on('console', message => {
            if(message.type() === 'error') {
                browserErrors.push(`${label} console error: ${message.text()}`);
            }
        });
        targetPage.on('requestfailed', request => {
            browserErrors.push(`${label} request failed: ${request.url()} (${request.failure()?.errorText || 'unknown error'})`);
        });
    };
    trackBrowserErrors(page, 'mobile user');
    let report = '\n\n## Mobile Registration Functional Test Report\n\n';

    try {
        execSync("sudo mysql -u root ultrax -e \"UPDATE pre_common_usergroup_field SET allowposttag=1;\"").toString();
        const suffix = Date.now().toString().slice(-8);
        const username = `m ${suffix}`;
        const email = `m${suffix}@example.com`;
        const password = 'Testpassword123!';

        console.log('Opening mobile registration...');
        const response = await page.goto('http://127.0.0.1:8080/member.php?mod=register');
        await page.waitForLoadState('networkidle');
        const touchHeader = await page.$('.header_toplogo');
        if(!touchHeader) {
            const cookies = await context.cookies();
            const mobileCookie = cookies.find(cookie => cookie.name === `discuz_${cookieSalt}_mobile`);
            const title = await page.title();
            const botReason = execSync("sudo mysql -u root ultrax -N -s -e \"SELECT bot_reason FROM pre_common_session WHERE ip = '127.0.0.1' ORDER BY lastactivity DESC LIMIT 1\"").toString().trim();
            throw new assert.AssertionError({
                message: `Assertion Error: Mobile registration did not render the touch template. URL=${page.url()}; title=${title}; mobileCookie=${mobileCookie ? mobileCookie.value : 'missing'}; botReason=${botReason || 'missing'}; responseStatus=${response ? response.status() : 'missing'}`,
            });
        }
        assert.ok(await page.$('#registerform'), 'Assertion Error: Mobile registration form did not render.');
        const footerLocaleLinks = page.locator('.footer-locales a[href^="misc.php?mod=i18n&key="]');
        assert.strictEqual(await footerLocaleLinks.count(), 3, 'Assertion Error: Touch footer locale switcher did not render all three locales.');

        const registrationForm = page.locator('#registerform');
        // reginput can rename the DOM id and name; the first text field is the username.
        const registrationTextFields = registrationForm.locator('input[type="text"]');
        assert.ok(await registrationTextFields.count() > 0, 'Assertion Error: Mobile registration username field did not render.');
        await registrationTextFields.nth(0).fill(username);
        const passInputs = registrationForm.locator('input[type="password"]');
        assert.strictEqual(await passInputs.count(), 2, 'Assertion Error: Mobile registration password and confirmation fields did not render.');
        await passInputs.nth(0).fill(password);
        await passInputs.nth(1).fill(password);
        const emailInput = registrationForm.locator('input[type="email"]');
        assert.strictEqual(await emailInput.count(), 1, 'Assertion Error: Mobile registration email field did not render.');
        await emailInput.fill(email);

        const secqaaInput = registrationForm.locator('input[name="secanswer"]');
        await secqaaInput.waitFor({ state: 'visible', timeout: 5000 });
        const secqaaQuestion = registrationForm.locator('[id^="vsecqaa_"]');
        await secqaaQuestion.waitFor({ state: 'visible', timeout: 5000 });
        assert.ok(
            (await secqaaQuestion.innerText()).includes('1+1=?'),
            'Assertion Error: Mobile registration security question was not visible.'
        );
        await secqaaInput.fill('2');

        const regSubmitBtn = registrationForm.locator('.btn_register button[name="regsubmit"]');
        assert.strictEqual(await regSubmitBtn.count(), 1, 'Assertion Error: Mobile registration submit button did not render.');
        let registrationPostData = '';
        let registrationContentType = '';
        page.on('request', request => {
            if(request.method() === 'POST' && request.url().includes('member.php?mod=register')) {
                registrationPostData = request.postData() || '';
                registrationContentType = request.headers()['content-type'] || '';
            }
        });
        const registrationResponse = page.waitForResponse(response => response.request().method() === 'POST' && response.url().includes('member.php?mod=register'));
        await regSubmitBtn.click();
        const submittedRegistration = await registrationResponse;
        const getSubmittedField = name => {
            if(registrationContentType.includes('multipart/form-data')) {
                const escapedName = name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                const match = registrationPostData.match(new RegExp(
                    `Content-Disposition: form-data; name="${escapedName}"\\r?\\n(?:Content-Type:[^\\r\\n]+\\r?\\n)?\\r?\\n([^\\r\\n]*)`
                ));
                return match ? match[1] : null;
            }
            return new URLSearchParams(registrationPostData).get(name);
        };
        assert.strictEqual(
            getSubmittedField('secanswer'),
            '2',
            `Assertion Error: Mobile registration did not submit the security answer. POST=${registrationPostData}`
        );
        assert.ok(
            getSubmittedField('secqaahash'),
            `Assertion Error: Mobile registration did not submit the security-question hash. POST=${registrationPostData}`
        );
        assert.ok(
            submittedRegistration.ok() || (submittedRegistration.status() >= 300 && submittedRegistration.status() < 400),
            `Assertion Error: Mobile registration POST failed with HTTP ${submittedRegistration.status()}.`
        );
        await page.waitForTimeout(500);

        const memberCount = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT COUNT(*) FROM pre_common_member WHERE username='${username}';"`).toString().trim();
        if(memberCount !== '1') {
            const responseText = await submittedRegistration.text();
            const challengeRows = execSync('sudo mysql -u root ultrax -N -s -e "SELECT ssid, code, verified, succeed FROM pre_common_seccheck ORDER BY ssid DESC LIMIT 5;"').toString().trim();
            throw new assert.AssertionError({
                message: `Assertion Error: Mobile registration did not create the member. Response=${responseText}; POST=${registrationPostData}; challenges=${challengeRows}`,
                actual: memberCount,
                expected: '1',
                operator: 'strictEqual',
            });
        }

        await page.goto('http://127.0.0.1:8080/home.php?mod=spacecp');
        await page.waitForLoadState('networkidle');
        assert.ok(await page.$('.header'), 'Assertion Error: Authenticated mobile page did not render the touch header.');
        assert.ok((await page.textContent('body')).includes(username), 'Assertion Error: Mobile registration did not establish a logged-in session.');
        await page.screenshot({ path: 'screenshot_mobile_01_registered.png' });

        const dbScalar = sql => execSync(`sudo mysql -u root ultrax -N -s -e "${sql}"`).toString().trim();
        const clickForResponse = async (control, predicate, label) => {
            assert.strictEqual(await control.count(), 1, `Assertion Error: ${label} control did not render exactly once.`);
            const [response] = await Promise.all([
                page.waitForResponse(predicate),
                control.click(),
            ]);
            assert.ok(
                response.ok() || (response.status() >= 300 && response.status() < 400),
                `Assertion Error: ${label} request failed with HTTP ${response.status()}.`
            );
            return response;
        };
        const waitForDbValue = async (sql, expected, message) => {
            for(let attempt = 0; attempt < 15; attempt++) {
                if(dbScalar(sql) === expected) {
                    return;
                }
                await page.waitForTimeout(500);
            }
            assert.fail(`${message}. Found: ${dbScalar(sql)}`);
        };
        const solveVisibleSecurityQuestion = async targetPage => {
            const answer = targetPage.locator('input[name="secanswer"]:visible');
            await answer.waitFor({ state: 'visible', timeout: 5000 });
            const question = targetPage.locator('[id^="vsecqaa_"]:visible');
            await question.waitFor({ state: 'visible', timeout: 5000 });
            assert.ok(
                (await question.innerText()).includes('1+1=?'),
                'Assertion Error: Mobile security question was not visible.'
            );
            await answer.fill('2');
        };
        const sendPrivateMessage = async (senderPage, recipient, message) => {
            await senderPage.goto('http://127.0.0.1:8080/home.php?mod=spacecp&ac=pm');
            await senderPage.waitForLoadState('networkidle');
            const pmForm = senderPage.locator('form[id^="pmform_"]:visible');
            assert.strictEqual(await pmForm.count(), 1, 'Assertion Error: Mobile PM compose form did not render.');
            const recipientInput = pmForm.locator('input[name="username"]');
            const messageInput = pmForm.locator('textarea[name="message"]');
            const submitButton = pmForm.locator('#pmsubmit_btn');
            assert.strictEqual(await recipientInput.count(), 1, 'Assertion Error: Mobile PM recipient field did not render.');
            assert.strictEqual(await messageInput.count(), 1, 'Assertion Error: Mobile PM message field did not render.');
            assert.strictEqual(await submitButton.count(), 1, 'Assertion Error: Mobile PM submit button did not render.');
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
            assert.ok(response.ok() || (status >= 300 && status < 400), `Assertion Error: Mobile PM send request failed: status=${status}; body=${responseText.slice(0, 2000)}`);
        };
        const subject = `Mobile thread ${suffix}`;
        const message = `Mobile thread body ${suffix}.`;
        const reply = `Mobile reply ${suffix}.`;
        const editedReply = `Mobile reply edited ${suffix}.`;
        const imagePath = 'static/image/common/logo.png';

        console.log('Testing native mobile album image upload...');
        await page.goto('http://127.0.0.1:8080/home.php?mod=spacecp&ac=upload');
        await page.waitForLoadState('networkidle');
        const albumImageInput = page.locator('#filedata');
        assert.strictEqual(await albumImageInput.count(), 1, 'Assertion Error: Mobile album upload control did not render.');
        const albumResponsePromise = page.waitForResponse(response =>
            response.request().method() === 'POST' &&
            response.url().includes('misc.php?mod=upload&operation=album')
        );
        await albumImageInput.setInputFiles(imagePath);
        const albumResponseText = await (await albumResponsePromise).text();
        const albumUpload = JSON.parse(albumResponseText);
        assert.ok(parseInt(albumUpload.picid, 10) > 0, `Assertion Error: Mobile album upload failed. Response: ${albumResponseText}`);
        await page.locator(`#imglist input[name="title[${albumUpload.picid}]"]`).waitFor({ state: 'attached', timeout: 5000 });

        const sortOptionTemplate = fs.readFileSync('template/default/touch/forum/post_sortoption.htm', 'utf8');
        assert.ok(
            sortOptionTemplate.includes('mobileUploadFiles({') && !sortOptionTemplate.includes('$.buildfileupload('),
            'Assertion Error: Mobile classified-information image upload still uses the legacy uploader.'
        );

        console.log('Posting mobile thread with image attachment...');
        await page.goto('http://127.0.0.1:8080/forum.php?mod=forumdisplay&fid=2');
        await page.waitForLoadState('domcontentloaded');
        const postThreadBtn = page.locator('a[href*="action=newthread"]');
        assert.strictEqual(await postThreadBtn.count(), 1, 'Assertion Error: Mobile new-thread control did not render.');
        await postThreadBtn.click();
        await page.waitForLoadState('domcontentloaded');
        assert.ok(await page.$('#postform #needsubject'), 'Assertion Error: Mobile new-thread form did not render.');
        await page.screenshot({ path: 'screenshot_mobile_editor.png' });
        await page.locator('#needsubject').fill(subject);
        await page.locator('#needmessage').fill(message);
        const imageInput = page.locator('#filedata');
        assert.strictEqual(await imageInput.count(), 1, 'Assertion Error: Mobile image upload control did not render.');
        const uploadResponsePromise = page.waitForResponse(response => response.request().method() === 'POST' && response.url().includes('misc.php?mod=upload'), { timeout: 60000 });
        // Instrument the page so a failed upload tells us exactly where the chain breaks
        await page.evaluate(() => {
            window.__diag = { fetchCalls: [], pageErrors: [], popupOpens: 0, changes: 0, changeTarget: '' };
            const origFetch = window.fetch;
            window.fetch = function(...args) {
                window.__diag.fetchCalls.push(String(args[0]).slice(0, 120));
                return origFetch.apply(this, args);
            };
            window.addEventListener('error', e => window.__diag.pageErrors.push(e.message));
            document.addEventListener('change', e => {
                window.__diag.changes++;
                window.__diag.changeTarget = (e.target && e.target.id) || (e.target && e.target.tagName) || 'unknown';
            }, true);
            if (window.popup && typeof popup.open === 'function') {
                const origOpen = popup.open;
                popup.open = function(...args) { window.__diag.popupOpens++; return origOpen.apply(popup, args); };
            }
        }).catch(() => {});
        await imageInput.setInputFiles(imagePath);
        let uploadText;
        try {
            uploadText = await (await uploadResponsePromise).text();
        } catch (uploadWaitError) {
            const diag = await page.evaluate(() => {
                const d = window.__diag || {};
                const scriptSummaries = Array.from(document.scripts).map(s => s.src ? ('src:' + s.src.split('/').pop()) : ('inline:' + (s.text || '').slice(0, 40).replace(/\s+/g, ' ')));
                return {
                    diag: d,
                    filedata: document.querySelectorAll('#filedata').length,
                    mobileDom: typeof mobileDom,
                    popup: typeof popup,
                    uploadsuccess: typeof uploadsuccess,
                    mobileUploadFiles: typeof mobileUploadFiles,
                    STATUSMSG: typeof STATUSMSG,
                    imgexts: typeof imgexts,
                    hasStatusMsgScript: Array.from(document.scripts).some(s => !s.src && (s.text || '').includes('STATUSMSG')),
                    scriptCount: document.scripts.length,
                    scripts: scriptSummaries.slice(-14),
                    bodyLength: document.body.innerHTML.length,
                };
            }).catch(err => ({ evalError: String(err) }));
            throw new assert.AssertionError({
                message: `Assertion Error: Mobile image upload POST did not occur (${uploadWaitError.message}); errors=${browserErrors.join(' | ') || 'none'}; diag=${JSON.stringify(diag).slice(0, 3000)}`,
            });
        }
        assert.match(uploadText, /^DISCUZUPLOAD\|1\|0\|\d+\|1\|/, `Assertion Error: Mobile image upload failed. Response: ${uploadText}`);
        await page.waitForFunction(() => document.querySelector('#imglist input[name^="attachnew["]'), null, { timeout: 15000 }).catch(async () => {
            const uploadListHtml = await page.$eval('#imglist', element => element.innerHTML).catch(() => 'missing');
            const callbackSource = await page.evaluate(() => typeof uploadsuccess === 'function' ? uploadsuccess.toString() : String(typeof uploadsuccess));
            throw new assert.AssertionError({
                message: `Assertion Error: Mobile upload did not append attachnew. Response: ${uploadText}; imglist=${uploadListHtml}; callback=${callbackSource}; errors=${browserErrors.join(' | ') || 'none'}`,
            });
        });
        const aid = await page.locator('#imglist input[name^="attachnew["]').evaluate(input => input.name.match(/^attachnew\[(\d+)\]/)[1]);
        await page.locator('#needmessage').fill(`${message} [attachimg]${aid}[/attachimg]`);
        const extraTagBtn = page.locator('#extra_tag_b');
        assert.strictEqual(await extraTagBtn.count(), 1, 'Assertion Error: Mobile tag control did not render.');
        await page.evaluate(() => {
            if (typeof window.showExtra === 'function') {
                window.showExtra('extra_tag');
            } else {
                const btn = document.getElementById('extra_tag_b');
                const panel = document.getElementById('extra_tag_c');
                if (btn) btn.classList.add('mon');
                if (panel) panel.style.display = 'block';
            }
        });
        // Modern chip UI (backported from desktop): #tags is hidden; type into #keyword-input and press Enter to call addKeyword()
        const tagInput = page.locator('#keyword-input:visible');
        assert.strictEqual(await tagInput.count(), 1, 'Assertion Error: Mobile tag input did not render after opening tag controls.');
        await tagInput.fill('mobile tag');
        await tagInput.press('Enter');
        await page.waitForFunction(() => (document.getElementById('tags')?.value || '').includes('mobile tag'), null, { timeout: 5000 });
        await page.waitForTimeout(250);
        await solveVisibleSecurityQuestion(page);
        const mobileThreadSubmit = page.locator('#postsubmit');
        await clickForResponse(
            mobileThreadSubmit,
            response => response.request().method() === 'POST' && response.url().includes('forum.php?mod=post'),
            'Mobile thread submit'
        );
        await waitForDbValue(`SELECT COUNT(*) FROM pre_forum_thread WHERE subject='${subject}'`, '1', 'Assertion Error: Mobile thread was not created');
        const tid = dbScalar(`SELECT tid FROM pre_forum_thread WHERE subject='${subject}' ORDER BY tid DESC LIMIT 1`);
        assert.ok(tid, 'Assertion Error: Mobile thread ID was not found.');
        const expectedTableId = (tid % 10).toString();
        await waitForDbValue(`SELECT tableid FROM pre_forum_attachment WHERE aid='${aid}' AND tid='${tid}'`, expectedTableId, 'Assertion Error: Mobile image attachment was not linked to its thread.');
        const isimage = dbScalar(`SELECT isimage FROM pre_forum_attachment_${expectedTableId} WHERE aid='${aid}' AND tid='${tid}' LIMIT 1`);
        assert.strictEqual(isimage, '1', 'Assertion Error: Mobile image upload was not stored as an image.');
        const threadAttach = dbScalar(`SELECT attachment FROM pre_forum_thread WHERE tid='${tid}'`);
        assert.strictEqual(threadAttach, '2', 'Assertion Error: Mobile thread attachment status was not set to 2.');

        await page.waitForURL(/forum\.php\?mod=viewthread/, { timeout: 5000 });
        await page.waitForLoadState('networkidle');
        assert.ok(page.url().includes(`tid=${tid}`), 'Assertion Error: Mobile image post redirected to the wrong thread.');
        assert.ok((await page.textContent('body')).includes(subject), 'Assertion Error: Mobile image thread subject was not rendered after submission.');
        const uploadedImagePath = uploadText.split('|')[5];
        assert.ok(uploadedImagePath, `Assertion Error: Mobile upload response did not contain an attachment path: ${uploadText}`);
        const renderedMobileImage = page.locator(`img[src$="${uploadedImagePath}"]`);
        assert.strictEqual(await renderedMobileImage.count(), 1, 'Assertion Error: Mobile image attachment was not rendered in viewthread.');
        assert.ok(await renderedMobileImage.evaluate(image => image.complete && image.naturalWidth > 0), 'Assertion Error: Mobile image attachment did not load.');
        await page.screenshot({ path: 'screenshot_mobile_02_thread_attachment.png' });

        console.log('Posting mobile thread with non-image attachment via UI...');
        const nonImgMobileSubject = `Mobile Non-Image Thread ${suffix}`;
        await page.goto('http://127.0.0.1:8080/forum.php?mod=post&action=newthread&fid=2');
        await page.waitForLoadState('networkidle');
        await page.locator('#needsubject').fill(nonImgMobileSubject);

        fs.mkdirSync('scratch', { recursive: true });
        const nonImgFileFixture = 'scratch/mobile_test_document.txt';
        fs.writeFileSync(nonImgFileFixture, 'Mobile test non-image attachment document content.');
        const mobileFileInput = page.locator('#attfiledata');
        assert.strictEqual(await mobileFileInput.count(), 1, 'Assertion Error: Mobile non-image upload control did not render.');
        const nonImgUploadResponse = page.waitForResponse(response => response.request().method() === 'POST' && response.url().includes('misc.php?mod=upload'));
        await mobileFileInput.setInputFiles(nonImgFileFixture);
        const nonImgUploadText = await (await nonImgUploadResponse).text();
        assert.match(nonImgUploadText, /^DISCUZUPLOAD\|0\|0\|\d+\|/, `Assertion Error: Mobile non-image upload failed. Response: ${nonImgUploadText}`);
        await page.waitForFunction(() => document.querySelector('#attlist input[name^="attachnew["]'), null, { timeout: 5000 });
        const mobileNonImgAid = await page.locator('#attlist input[name^="attachnew["]').evaluate(input => input.name.match(/^attachnew\[(\d+)\]/)[1]);

        await page.locator('#needmessage').fill(`Mobile non-image attachment body ${suffix}. [attach]${mobileNonImgAid}[/attach]`);
        await page.waitForFunction(() => document.getElementById('postsubmit')?.getAttribute('data-disabled') === 'false');
        await solveVisibleSecurityQuestion(page);

        const mobileNonImageSubmit = page.locator('#postsubmit');
        await clickForResponse(
            mobileNonImageSubmit,
            response => response.request().method() === 'POST' && response.url().includes('forum.php?mod=post'),
            'Mobile non-image thread submit'
        );
        await page.waitForURL(/forum\.php\?mod=viewthread/, { timeout: 5000 });
        await page.waitForLoadState('networkidle');
        const nonImgMobileTid = dbScalar(`SELECT tid FROM pre_forum_thread WHERE subject='${nonImgMobileSubject}' ORDER BY tid DESC LIMIT 1`);
        assert.ok(nonImgMobileTid, 'Assertion Error: Mobile thread with non-image attachment was not created.');
        assert.ok(page.url().includes(`tid=${nonImgMobileTid}`), 'Assertion Error: Mobile non-image post redirected to the wrong thread.');
        assert.ok((await page.textContent('body')).includes('mobile_test_document.txt'), 'Assertion Error: Mobile non-image attachment was not rendered in viewthread.');
        await page.screenshot({ path: 'screenshot_mobile_attachment_non_image_viewthread.png' });

        console.log('Replying to mobile thread...');
        const replyBtn = page.locator('a.flex[href*="action=reply"][href*="reppost="]');
        assert.strictEqual(await replyBtn.count(), 1, 'Assertion Error: Mobile reply control did not render.');
        await replyBtn.click();
        await page.waitForLoadState('networkidle');
        assert.ok(await page.$('#postform #needmessage'), 'Assertion Error: Mobile reply form did not render.');
        await page.locator('#needmessage').fill(reply);
        await page.waitForFunction(() => document.getElementById('postsubmit')?.getAttribute('data-disabled') === 'false');
        await solveVisibleSecurityQuestion(page);
        const mobileReplySubmit = page.locator('#postsubmit');
        await clickForResponse(
            mobileReplySubmit,
            response => response.request().method() === 'POST' && response.url().includes('forum.php?mod=post&action=reply'),
            'Mobile reply submit'
        );
        await page.waitForURL(/forum\.php\?mod=viewthread/, { timeout: 5000 });
        await page.waitForLoadState('networkidle');
        await waitForDbValue(`SELECT COUNT(*) FROM pre_forum_post WHERE tid='${nonImgMobileTid}' AND message='${reply}'`, '1', 'Assertion Error: Mobile reply was not created');
        const replyPid = dbScalar(`SELECT pid FROM pre_forum_post WHERE tid='${nonImgMobileTid}' AND message='${reply}' ORDER BY pid DESC LIMIT 1`);
        assert.ok(replyPid, 'Assertion Error: Mobile reply ID was not found.');

        console.log('Editing mobile reply...');
        assert.ok(page.url().includes('viewthread'), 'Assertion Error: Mobile reply submit did not redirect to viewthread.');
        const manageLink = page.locator(`a[href="#moption_${replyPid}"]`);
        assert.strictEqual(await manageLink.count(), 1, 'Assertion Error: Mobile post management control did not render.');
        await manageLink.click();
        const editLink = page.locator(`#moption_${replyPid}_popmenu a[href*="action=edit"][href*="pid=${replyPid}"]:visible`);
        assert.strictEqual(await editLink.count(), 1, 'Assertion Error: Mobile edit control did not render.');
        await editLink.click();
        await page.waitForLoadState('networkidle');
        assert.ok(page.url().includes('mod=post&action=edit'), 'Assertion Error: Mobile edit control did not navigate to the edit form.');
        assert.ok(await page.$('#postform #needmessage'), 'Assertion Error: Mobile edit form did not render.');
        await page.locator('#needmessage').fill(editedReply);
        await page.waitForFunction(() => document.getElementById('postsubmit')?.getAttribute('data-disabled') === 'false');
        const mobileEditSubmit = page.locator('#postsubmit');
        await clickForResponse(
            mobileEditSubmit,
            response => response.request().method() === 'POST' && response.url().includes('forum.php?mod=post&action=edit'),
            'Mobile edit submit'
        );
        await page.waitForURL(/forum\.php\?mod=viewthread/, { timeout: 5000 });
        await page.waitForLoadState('networkidle');
        await waitForDbValue(`SELECT message FROM pre_forum_post WHERE pid='${replyPid}'`, editedReply, 'Assertion Error: Mobile reply edit was not saved');

        assert.ok(page.url().includes('viewthread'), 'Assertion Error: Mobile edit submit did not redirect to viewthread.');
        await page.waitForLoadState('networkidle');
        assert.ok((await page.textContent('body')).includes(editedReply), 'Assertion Error: Edited mobile reply was not rendered in the thread.');
        await page.screenshot({ path: 'screenshot_mobile_03_reply_edited.png' });

        console.log('Testing mobile forum.php (forum index)...');
        await page.goto('http://127.0.0.1:8080/forum.php');
        await page.waitForLoadState('networkidle');
        assert.ok((await page.textContent('body')).includes('Home'), 'Assertion Error: Mobile forum index did not render its navigation.');
        await page.screenshot({ path: 'screenshot_mobile_04_forum_index.png' });

        console.log('Testing mobile forumdisplay.php (fid=2)...');
        await page.goto('http://127.0.0.1:8080/forum.php?mod=forumdisplay&fid=2');
        await page.waitForLoadState('networkidle');
        assert.ok((await page.textContent('body')).includes(subject), 'Assertion Error: Mobile forumdisplay did not show the created thread.');
        await page.screenshot({ path: 'screenshot_mobile_05_forumdisplay.png' });

        const uid = dbScalar(`SELECT uid FROM pre_common_member WHERE username='${username}' LIMIT 1`);

        console.log('Testing mobile "My" center page...');
        await page.goto(`http://127.0.0.1:8080/home.php?mod=space&uid=${uid}&do=profile&mycenter=1`);
        await page.waitForLoadState('networkidle');
        assert.ok((await page.textContent('body')).includes(username), 'Assertion Error: Mobile My Center did not load the current user.');
        await page.screenshot({ path: 'screenshot_mobile_06_my_center.png' });

        console.log("Testing mobile other user's profile page (admin uid=1)...");
        await page.goto('http://127.0.0.1:8080/home.php?mod=space&uid=1&do=profile');
        await page.waitForLoadState('networkidle');
        const mobileOtherProfileBody = await page.textContent('body');
        assert.ok(mobileOtherProfileBody.includes('admin'), 'Assertion Error: Mobile other user profile page did not load.');
        await page.screenshot({ path: 'screenshot_mobile_other_user_profile.png' });

        console.log("Testing mobile User Replies Page (home.php?mod=space&do=thread&view=me&type=reply)...");
        await page.goto('http://127.0.0.1:8080/home.php?mod=space&do=thread&view=me&type=reply');
        await page.waitForLoadState('networkidle');
        const mobileReplyBody = await page.textContent('body');
        assert.ok(
            mobileReplyBody.includes(editedReply),
            'Assertion Error: Mobile view=me&type=reply user replies page did not load correctly.'
        );
        await page.screenshot({ path: 'screenshot_mobile_space_thread_reply.png' });

        console.log('Posting first-floor postcomment on mobile via UI...');
        const mobileFirstFloorPid = dbScalar(`SELECT pid FROM pre_forum_post WHERE tid='${tid}' AND first=1 LIMIT 1`);
        assert.ok(mobileFirstFloorPid, 'Assertion Error: Mobile first floor post ID was not found.');

        await page.goto(`http://127.0.0.1:8080/forum.php?mod=viewthread&tid=${tid}`);
        await page.waitForLoadState('networkidle');

        const mobileFirstFloorCommentLink = page.locator(`a.dialog[href*="action=comment"][href*="pid=${mobileFirstFloorPid}"]`);
        assert.strictEqual(await mobileFirstFloorCommentLink.count(), 1, 'Assertion Error: Mobile comment control did not render for the first floor post.');
        await mobileFirstFloorCommentLink.click();

        const mobileFirstFloorCommentForm = page.locator('#ntcmsg_popmenu #floatlayout_comment form#commentform');
        await mobileFirstFloorCommentForm.waitFor({ state: 'visible' });
        const mobileFirstFloorMsgBox = mobileFirstFloorCommentForm.locator('#commentmessage');
        const mobileFirstFloorSubmitBtn = mobileFirstFloorCommentForm.locator('#commentsubmit');
        assert.strictEqual(await mobileFirstFloorMsgBox.count(), 1, 'Assertion Error: Mobile first floor comment message input did not render.');
        assert.strictEqual(await mobileFirstFloorSubmitBtn.count(), 1, 'Assertion Error: Mobile first floor comment submit button did not render.');

        const mobileFirstFloorCommentText = 'Mobile test comment on first floor.';
        await mobileFirstFloorMsgBox.fill(mobileFirstFloorCommentText);
        await solveVisibleSecurityQuestion(page);
        const [mobileFirstFloorResponse] = await Promise.all([
            page.waitForResponse(response => response.request().method() === 'POST' && response.url().includes('mod=post') && response.url().includes('commentsubmit=yes')),
            mobileFirstFloorSubmitBtn.click()
        ]);
        assert.ok(mobileFirstFloorResponse.ok(), `Assertion Error: Mobile first-floor comment request failed with HTTP ${mobileFirstFloorResponse.status()}.`);

        const mobileFirstFloorDbCheck = dbScalar(`SELECT COUNT(*) FROM pre_forum_postcomment WHERE authorid='${uid}' AND pid='${mobileFirstFloorPid}' AND comment='${mobileFirstFloorCommentText}'`);
        assert.strictEqual(mobileFirstFloorDbCheck, '1', 'Assertion Error: Mobile first floor comment was not created in database.');

        await page.goto(`http://127.0.0.1:8080/forum.php?mod=viewthread&tid=${tid}`);
        await page.waitForLoadState('networkidle');
        assert.ok((await page.textContent('body')).includes(mobileFirstFloorCommentText), 'Assertion Error: Mobile first-floor comment was not rendered in viewthread.');
        await page.screenshot({ path: 'screenshot_mobile_viewthread_commented_first_floor.png' });

        console.log('Posting postcomment on mobile via UI and testing type=postcomment page...');
        const mobilePostCommentText = 'Mobile test postcomment text.';
        const adminReplyPid = dbScalar("SELECT pid FROM pre_forum_post WHERE authorid=1 AND first=0 AND message LIKE '%Admin quote reply to user thread.%' ORDER BY pid DESC LIMIT 1");
        const adminReplyTid = dbScalar(`SELECT tid FROM pre_forum_post WHERE pid='${adminReplyPid}'`);
        assert.ok(adminReplyPid && adminReplyTid, 'Assertion Error: Admin reply target for the mobile post comment was not found.');

        await page.goto(`http://127.0.0.1:8080/forum.php?mod=viewthread&tid=${adminReplyTid}`);
        await page.waitForLoadState('networkidle');
        const mobileCommentLink = page.locator(`a.dialog[href*="action=comment"][href*="pid=${adminReplyPid}"]`);
        assert.strictEqual(await mobileCommentLink.count(), 1, 'Assertion Error: Mobile comment control did not render for the admin reply.');
        await mobileCommentLink.click();

        const mobileCommentForm = page.locator('#ntcmsg_popmenu #floatlayout_comment form#commentform');
        await mobileCommentForm.waitFor({ state: 'visible' });
        const mobileCommentMsgBox = mobileCommentForm.locator('#commentmessage');
        const mobileSubmitCommentBtn = mobileCommentForm.locator('#commentsubmit');
        assert.strictEqual(await mobileCommentMsgBox.count(), 1, 'Assertion Error: Mobile post comment message input did not render.');
        assert.strictEqual(await mobileSubmitCommentBtn.count(), 1, 'Assertion Error: Mobile post comment submit button did not render.');
        await mobileCommentMsgBox.fill(mobilePostCommentText);
        await solveVisibleSecurityQuestion(page);
        const [mobilePostCommentResponse] = await Promise.all([
            page.waitForResponse(response => response.request().method() === 'POST' && response.url().includes('mod=post') && response.url().includes('commentsubmit=yes')),
            mobileSubmitCommentBtn.click()
        ]);
        assert.ok(mobilePostCommentResponse.ok(), `Assertion Error: Mobile post comment request failed with HTTP ${mobilePostCommentResponse.status()}.`);

        const mobilePostCommentDbCheck = dbScalar(`SELECT COUNT(*) FROM pre_forum_postcomment WHERE authorid='${uid}' AND pid='${adminReplyPid}' AND comment='${mobilePostCommentText}'`);
        assert.strictEqual(mobilePostCommentDbCheck, '1', 'Assertion Error: Mobile post comment was not created in database.');

        // Navigate back to viewthread to verify and screenshot the postcomment
        await page.goto(`http://127.0.0.1:8080/forum.php?mod=viewthread&tid=${adminReplyTid}`);
        await page.waitForLoadState('networkidle');
        assert.ok((await page.textContent('body')).includes(mobilePostCommentText), 'Assertion Error: Mobile post comment was not rendered in viewthread.');
        await page.screenshot({ path: 'screenshot_mobile_viewthread_commented.png' });

        await page.goto('http://127.0.0.1:8080/home.php?mod=space&do=thread&view=me&type=postcomment');
        await page.waitForLoadState('networkidle');
        const mobilePostcommentBody = await page.textContent('body');
        assert.ok(
            mobilePostcommentBody.includes(mobilePostCommentText),
            'Assertion Error: Mobile view=me&type=postcomment page did not load correctly.'
        );
        await page.screenshot({ path: 'screenshot_mobile_space_thread_postcomment.png' });

        console.log('Testing mobile Thread Recommendation and Hot Reply Voting via UI...');
        const targetMobileRecommendTid = dbScalar("SELECT tid FROM pre_forum_thread WHERE authorid=1 ORDER BY tid DESC LIMIT 1");
        assert.match(targetMobileRecommendTid, /^\d+$/, 'Assertion Error: Seeded admin thread for mobile recommendation testing was not found.');

        await page.goto(`http://127.0.0.1:8080/forum.php?mod=viewthread&tid=${targetMobileRecommendTid}`);
        await page.waitForLoadState('networkidle');
        const mobileRecommendBtn = page.locator('a.dialog[href*="action=recommend&do=add"]');
        assert.strictEqual(await mobileRecommendBtn.count(), 1, 'Assertion Error: Mobile thread recommend button did not render.');
        assert.ok(await mobileRecommendBtn.isVisible(), 'Assertion Error: Mobile thread recommend button was not visible.');
        const mobileRecommendCount = page.locator('#recommendv_add');
        assert.strictEqual(await mobileRecommendCount.count(), 1, 'Assertion Error: Mobile recommendation count did not render.');
        const mobileRecommendBefore = Number((await mobileRecommendCount.textContent()).trim() || '0');
        console.log("Clicking mobile thread recommend button via UI...");
        await clickForResponse(
            mobileRecommendBtn,
            response => response.url().includes('action=recommend&do=add'),
            'Mobile thread recommendation'
        );
        assert.strictEqual(
            dbScalar(`SELECT COUNT(*) FROM pre_forum_memberrecommend WHERE tid='${targetMobileRecommendTid}' AND recommenduid='${uid}'`),
            '1',
            'Assertion Error: Mobile thread recommendation was not persisted.'
        );
        await page.reload({ waitUntil: 'networkidle' });
        assert.ok(
            Number((await page.locator('#recommendv_add').textContent()).trim() || '0') > mobileRecommendBefore,
            'Assertion Error: Mobile recommendation count did not increase in the rendered UI.'
        );

        await page.goto(`http://127.0.0.1:8080/forum.php?mod=viewthread&tid=${adminReplyTid}`);
        await page.waitForLoadState('networkidle');
        const mobileSupportBtn = page.locator(`a.dialog[href*="action=postreview&do=support"][href*="pid=${adminReplyPid}"]`);
        assert.strictEqual(await mobileSupportBtn.count(), 1, 'Assertion Error: Mobile postreview support button did not render.');
        assert.ok(await mobileSupportBtn.isVisible(), 'Assertion Error: Mobile postreview support button was not visible.');
        const mobileSupportCount = page.locator(`#review_support_${adminReplyPid}`);
        assert.strictEqual(await mobileSupportCount.count(), 1, 'Assertion Error: Mobile postreview support count did not render.');
        const mobileSupportBefore = Number((await mobileSupportCount.textContent()).trim() || '0');
        console.log("Clicking mobile postreview support button via UI...");
        await clickForResponse(
            mobileSupportBtn,
            response => response.url().includes('action=postreview&do=support'),
            'Mobile postreview support'
        );
        assert.strictEqual(
            dbScalar(`SELECT COUNT(*) FROM pre_forum_hotreply_member WHERE pid='${adminReplyPid}' AND uid='${uid}' AND attitude=1`),
            '1',
            'Assertion Error: Mobile postreview support vote was not persisted.'
        );
        await page.reload({ waitUntil: 'networkidle' });
        assert.ok(
            Number((await page.locator(`#review_support_${adminReplyPid}`).textContent()).trim() || '0') > mobileSupportBefore,
            'Assertion Error: Mobile postreview support count did not increase in the rendered UI.'
        );

        await page.goto(`http://127.0.0.1:8080/forum.php?mod=viewthread&tid=${adminReplyTid}`);
        await page.waitForLoadState('networkidle');
        await page.screenshot({ path: 'screenshot_mobile_thread_recommend.png' });

        console.log('Testing mobile UI avatar setup with file upload via UI...');
        const avatarPageResponse = await page.goto('http://127.0.0.1:8080/home.php?mod=spacecp&ac=avatar');
        await page.waitForLoadState('networkidle');
        if (!avatarPageResponse || !avatarPageResponse.ok()) {
            const responseBody = avatarPageResponse ? await avatarPageResponse.text() : '';
            assert.fail(`Mobile avatar page failed: status=${avatarPageResponse ? avatarPageResponse.status() : 'missing'}; body=${responseBody.slice(0, 4000)}`);
        }
        const mobileAvatarFixture = 'static/image/smiley/BQ2/alu1.jpg';
        const mobileAvatarInput = page.locator('#avatarfile');
        const mobileAvatarConfirm = page.locator('#avconfirm');
        assert.strictEqual(await mobileAvatarInput.count(), 1, 'Assertion Error: Mobile avatar file control did not render.');
        assert.strictEqual(await mobileAvatarConfirm.count(), 1, 'Assertion Error: Mobile avatar confirmation control did not render.');
        assert.ok(fs.existsSync(mobileAvatarFixture), 'Assertion Error: Mobile avatar fixture is missing.');
        await mobileAvatarInput.setInputFiles(mobileAvatarFixture);
        await page.locator('#avataradjuster2').waitFor({ state: 'visible' });
        const [mobileAvatarResponse] = await Promise.all([
            page.waitForResponse(response => response.request().method() === 'POST' && response.url().includes('/api/avatar/index.php')),
            mobileAvatarConfirm.click()
        ]);
        assert.ok(mobileAvatarResponse.ok(), `Assertion Error: Mobile avatar upload failed with HTTP ${mobileAvatarResponse.status()}.`);
        const mobileAvatarFinished = page.locator('.finishbutton:visible');
        await mobileAvatarFinished.waitFor({ state: 'visible' });
        assert.strictEqual(await mobileAvatarFinished.count(), 1, 'Assertion Error: Mobile avatar completion control did not render exactly once.');
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle' }),
            mobileAvatarFinished.click()
        ]);
        await waitForDbValue(`SELECT avatarstatus FROM pre_common_member WHERE uid='${uid}'`, '1', 'Assertion Error: Mobile user avatarstatus in database was not 1');

        console.log('Testing mobile viewthread thread tag rendering...');
        await page.goto(`http://127.0.0.1:8080/forum.php?mod=viewthread&tid=${tid}`);
        await page.waitForLoadState('networkidle');
        const tagid = dbScalar("SELECT tagid FROM pre_common_tag WHERE tagname='mobile tag' LIMIT 1");
        assert.match(tagid, /^\d+$/, 'Assertion Error: Mobile thread tag was not stored in the database.');
        const viewthreadTagBody = await page.textContent('body');
        assert.ok(viewthreadTagBody.includes('mobile tag'), 'Assertion Error: Thread tag "mobile tag" submitted during thread creation was not rendered in mobile viewthread.');
        await page.screenshot({ path: 'screenshot_mobile_08_viewthread_tag.png' });

        console.log('Testing mobile reply notification (do=notice) via UI quote reply...');
        const quoteMobilePid = replyPid;
        const adminMobileContext = await browser.newContext({
            viewport: { width: 390, height: 844 },
            locale: 'en-US',
        });
        await adminMobileContext.addCookies([
            { name: `discuz_${cookieSalt}_mobile`, value: '2', url: 'http://127.0.0.1:8080' },
        ]);
        const adminMobilePage = await adminMobileContext.newPage();
        trackBrowserErrors(adminMobilePage, 'mobile admin');
        await adminMobilePage.goto('http://127.0.0.1:8080/member.php?mod=logging&action=login');
        await adminMobilePage.waitForLoadState('networkidle');
        const adminLoginForm = adminMobilePage.locator('form[id^="loginform"]:visible');
        assert.strictEqual(await adminLoginForm.count(), 1, 'Assertion Error: Mobile admin login form did not render.');
        await adminLoginForm.locator('input[name="username"]').fill('admin');
        await adminLoginForm.locator('input[name="password"]').fill('Testpassword123!');
        await solveVisibleSecurityQuestion(adminMobilePage);
        const adminLoginSubmit = adminLoginForm.locator('button[type="submit"]:visible');
        assert.strictEqual(await adminLoginSubmit.count(), 1, 'Assertion Error: Mobile admin login submit control did not render.');
        const [adminLoginResponse] = await Promise.all([
            adminMobilePage.waitForResponse(response =>
                response.request().method() === 'POST' &&
                response.url().includes('member.php?mod=logging')
            ),
            adminLoginSubmit.click()
        ]);
        assert.ok(
            adminLoginResponse.ok() || (adminLoginResponse.status() >= 300 && adminLoginResponse.status() < 400),
            `Assertion Error: Mobile admin login POST failed with HTTP ${adminLoginResponse.status()}.`
        );
        await adminMobilePage.waitForFunction(
            () => Number(window.discuz_uid || 0) === 1,
            null,
            { timeout: 5000 }
        );
        assert.strictEqual(
            await adminMobilePage.evaluate(() => Number(window.discuz_uid || 0)),
            1,
            'Assertion Error: Mobile admin login did not establish the expected browser session.'
        );
        await adminMobileContext.addCookies([
            { name: `discuz_${cookieSalt}_mobile`, value: '2', url: 'http://127.0.0.1:8080' },
        ]);
        const adminPmToMobileUser = 'Admin PM for mobile inbox.';
        await sendPrivateMessage(adminMobilePage, username, adminPmToMobileUser);
        assert.strictEqual(
            dbScalar(`SELECT COUNT(*) FROM pre_common_pm_message p INNER JOIN pre_common_pm_member m ON m.plid=p.plid WHERE m.uid='${uid}' AND p.authorid='1' AND p.message='${adminPmToMobileUser}'`),
            '1',
            'Assertion Error: Admin PM was not delivered to the mobile user inbox.'
        );
        await adminMobilePage.goto(`http://127.0.0.1:8080/forum.php?mod=post&action=reply&fid=2&tid=${nonImgMobileTid}`);
        await adminMobilePage.waitForLoadState('networkidle');
        const adminReplyText = 'Admin original reply to user thread.';
        const adminMsgArea = adminMobilePage.locator('#needmessage:visible');
        assert.strictEqual(await adminMsgArea.count(), 1, 'Assertion Error: Mobile reply editor for admin did not render.');
        await adminMsgArea.fill(adminReplyText);
        await solveVisibleSecurityQuestion(adminMobilePage);
        const adminSubmitBtn = adminMobilePage.locator('#postsubmit:visible');
        assert.strictEqual(await adminSubmitBtn.count(), 1, 'Assertion Error: Mobile reply submit button for admin did not render.');
        await adminMobilePage.waitForFunction(() => document.getElementById('postsubmit')?.dataset.disabled === 'false');
        await Promise.all([
            adminMobilePage.waitForResponse(response =>
                response.request().method() === 'POST' &&
                response.url().includes('forum.php?mod=post&action=reply')
            ),
            adminSubmitBtn.click()
        ]);
        await waitForDbValue(
            `SELECT COUNT(*) FROM pre_forum_post WHERE tid='${nonImgMobileTid}' AND authorid='1' AND message LIKE '%${adminReplyText}%'`,
            '1',
            'Assertion Error: Admin reply was not stored in database.'
        );
        const adminReplyPidNonImg = dbScalar(`SELECT pid FROM pre_forum_post WHERE tid='${nonImgMobileTid}' AND authorid='1' AND message LIKE '%${adminReplyText}%' ORDER BY pid DESC LIMIT 1`);
        assert.match(adminReplyPidNonImg, /^\d+$/, 'Assertion Error: Failed to retrieve Admin reply PID.');

        console.log('Testing normal user quoting admin reply in touch template...');
        await page.goto(`http://127.0.0.1:8080/forum.php?mod=viewthread&tid=${nonImgMobileTid}`);
        await page.waitForLoadState('networkidle');
        const userQuoteBtn = page.locator(`a[href*="action=reply"][href*="repquote=${adminReplyPidNonImg}"]`);
        assert.strictEqual(await userQuoteBtn.count(), 1, 'Assertion Error: Mobile user quote-reply control for admin reply did not render.');
        await Promise.all([
            page.waitForURL(url =>
                url.href.includes('mod=post') &&
                url.href.includes('action=reply') &&
                url.href.includes(`repquote=${adminReplyPidNonImg}`)
            ),
            userQuoteBtn.click()
        ]);
        await page.waitForLoadState('networkidle');
        const userQuoteReplyText = 'User mobile quote reply to admin reply.';
        const userMsgArea = page.locator('#needmessage:visible');
        assert.strictEqual(await userMsgArea.count(), 1, 'Assertion Error: Mobile quote reply editor for user did not render.');
        await userMsgArea.fill(userQuoteReplyText);
        await solveVisibleSecurityQuestion(page);
        const userSubmitBtn = page.locator('#postsubmit:visible');
        assert.strictEqual(await userSubmitBtn.count(), 1, 'Assertion Error: Mobile quote reply submit button for user did not render.');
        await page.waitForFunction(() => document.getElementById('postsubmit')?.dataset.disabled === 'false');
        await Promise.all([
            page.waitForResponse(response =>
                response.request().method() === 'POST' &&
                response.url().includes('forum.php?mod=post&action=reply')
            ),
            userSubmitBtn.click()
        ]);
        await waitForDbValue(
            `SELECT COUNT(*) FROM pre_forum_post WHERE tid='${nonImgMobileTid}' AND authorid='${uid}' AND message LIKE '%${userQuoteReplyText}%'`,
            '1',
            'Assertion Error: Normal user quote reply to admin was not stored.'
        );

        console.log('Testing mobile PM center page...');
        await page.goto('http://127.0.0.1:8080/home.php?mod=space&do=pm');
        await page.waitForLoadState('networkidle');
        const mobilePmBody = await page.textContent('body');
        assert.ok(mobilePmBody.includes(adminPmToMobileUser), 'Assertion Error: Mobile PM center did not display the delivered admin message.');
        await page.screenshot({ path: 'screenshot_mobile_07_pm.png' });

        console.log('Checking if Admin received notification for user quote reply in touch template...');
        await adminMobilePage.goto('http://127.0.0.1:8080/home.php?mod=space&do=notice');
        await adminMobilePage.waitForLoadState('networkidle');
        const adminNoticeBody = await adminMobilePage.textContent('body');
        assert.ok(
            adminNoticeBody.includes(userQuoteReplyText) || adminNoticeBody.includes(username),
            'Assertion Error: Touch notification page for admin did not render the user quote reply notification.'
        );
        await adminMobilePage.screenshot({ path: 'screenshot_mobile_09_notice.png' });
        await adminMobileContext.close();

        assert.deepStrictEqual(browserErrors, [], `Assertion Error: Browser errors occurred during mobile UI tests:\n${browserErrors.join('\n')}`);
        report += `### Touch Registration, Posting, Replying, Editing, Forum Index, Forumdisplay, My Center, PM Center, Thread Tag and Notice Center\n- **Status**: Checked\n- **Username**: ${username}\n- **Thread**: ${tid}\n- **Reply**: ${replyPid}\n- **Image Attachment**: ${aid}\n- **Tag**: mobile tag (ID: ${tagid})\n- **Screenshots**:\n  - \`screenshot_mobile_editor.png\`\n  - \`screenshot_mobile_01_registered.png\`\n  - \`screenshot_mobile_02_thread_attachment.png\`\n  - \`screenshot_mobile_03_reply_edited.png\`\n  - \`screenshot_mobile_04_forum_index.png\`\n  - \`screenshot_mobile_05_forumdisplay.png\`\n  - \`screenshot_mobile_06_my_center.png\`\n  - \`screenshot_mobile_other_user_profile.png\`\n  - \`screenshot_mobile_space_thread_reply.png\`\n  - \`screenshot_mobile_space_thread_postcomment.png\`\n  - \`screenshot_mobile_07_pm.png\`\n  - \`screenshot_mobile_08_viewthread_tag.png\`\n  - \`screenshot_mobile_09_notice.png\`\n\n`;
    } catch(error) {
        console.error('Test execution failed:', error);
        process.exitCode = 1;
        try {
            const currentUrl = page.url();
            const pageTitle = await page.title().catch(() => 'Unknown Title');
            const pageSource = await page.content().catch(() => '');
            if (pageSource) {
                fs.writeFileSync('mobile_page_source.html', pageSource);
                fs.writeFileSync('browser_page_source.html', pageSource);
            }
            await page.screenshot({ path: 'screenshot_mobile_failure.png', fullPage: true }).catch(() => {});
            const errLog = `[Mobile Failure] URL: ${currentUrl} | Title: ${pageTitle}\nError: ${error.stack || error.message}\nPage Source saved to mobile_page_source.html\n---\n`;
            fs.appendFileSync('browser_error.txt', errLog);
        } catch (e) {
            console.error('Failed to capture failure state:', e.message);
        }
        report += `## Error Encountered\n\`\`\`\n${error.message}\n\`\`\`\n\n`;
        try {
            const token = process.env.GITHUB_TOKEN || process.env.GH_TOKEN;
            if (token) {
                const gistBody = (report + "\n\n--- browser_error.txt ---\n" + (fs.existsSync('browser_error.txt') ? fs.readFileSync('browser_error.txt','utf8') : '')).slice(0, 90000);
                await fetch('https://api.github.com/repos/hbghlyj/kuing.cjhb.site/pulls/' + ((process.env.GITHUB_REF || '').match(/refs\/pull\/(\d+)\//) || [,'675'])[1] + '/reviews', { method: 'POST', headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json', 'Accept': 'application/vnd.github.v3+json' }, body: JSON.stringify({ body: '**FAIL ' + suffix + ' mobile**\n\n```\n' + gistBody.slice(0,50000) + '\n```', event: 'COMMENT', commit_id: process.env.GITHUB_SHA || undefined }) }).then(r=>r.json()).then(j=> console.log('REVIEW_CREATED ' + (j.html_url || JSON.stringify(j)))).catch(e=> console.log('review error', e.message));
            }
        } catch {}
    } finally {
        await browser.close();
        if(fs.existsSync('mobile_test_image.png')) {
            fs.unlinkSync('mobile_test_image.png');
        }
        fs.appendFileSync('functional_test_report.md', report);
        console.log('Mobile registration tests completed and report appended.');
    }
})();
