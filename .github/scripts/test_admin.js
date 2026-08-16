const { chromium } = require('playwright');
const fs = require('fs');
const assert = require('assert');
const { execSync } = require('child_process');
const { reportCiFailure } = require('./report_ci_failure');

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
            throw new Error(`Console error in browser: ${msg.text()}`);
        }
    });
    page.on('requestfailed', request => {
        throw new Error(`Browser request failed: ${request.url()} (${request.failure()?.errorText || 'unknown error'})`);
    });

    let report = "\n\n## Admin Panel Functional Test Report\n\n";
    console.log("Starting Admin Panel tests...");

    try {
        const username = 'admin';
        const password = 'Testpassword123!';

        console.log("Phase 2: Admin Account Testing");
        await page.goto('http://127.0.0.1:8080/member.php?mod=logging&action=login');
        await page.waitForLoadState('domcontentloaded');
        const adminLoginForm = page.locator('form[id^="loginform_"]:visible');
        assert.strictEqual(await adminLoginForm.count(), 1, 'Assertion Error: Admin login form did not render.');
        await adminLoginForm.locator('input[name="username"]').fill('admin');
        await adminLoginForm.locator('input[name="password"]').fill('Testpassword123!');
        const secqaa = adminLoginForm.locator('input[name*="secanswer"]');
        if (await secqaa.count()) await secqaa.fill('2');
        const submitBtn = adminLoginForm.locator('button[name="loginsubmit"], button[type="submit"], input[type="submit"]');
        assert.strictEqual(await submitBtn.count(), 1, 'Assertion Error: Admin login submit control did not render.');
        const [loginResponse] = await Promise.all([
            page.waitForResponse(response =>
                response.request().method() === 'POST' &&
                response.url().includes('member.php?mod=logging')
            ),
            page.waitForNavigation({ waitUntil: 'load' }),
            submitBtn.click()
        ]);
        assert.ok(
            loginResponse.ok() || (loginResponse.status() >= 300 && loginResponse.status() < 400),
            `Assertion Error: Admin login POST failed with HTTP ${loginResponse.status()}.`
        );
        await page.goto('http://127.0.0.1:8080/home.php?mod=spacecp');
        await page.waitForLoadState('domcontentloaded');
        assert.strictEqual(
            await page.locator('form[id^="loginform_"]:visible').count(),
            0,
            'Assertion Error: Admin frontend login did not establish an authenticated session.'
        );
        assert.ok((await page.textContent('body')).includes(username), 'Assertion Error: Authenticated account page did not render the admin username.');
        report += '### 1. Admin Authentication\n- **Status**: Checked\n\n';

        const profileInfoLink = page.locator('a[href*="ac=profile"][href*="op=info"]');
        assert.strictEqual(await profileInfoLink.count(), 1, 'Assertion Error: Admin profile information tab did not render.');
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'load' }),
            profileInfoLink.click()
        ]);
        const bioInput = page.locator('textarea[name="bio"]');
        assert.strictEqual(await bioInput.count(), 1, 'Assertion Error: Admin profile bio field did not render.');
        await bioInput.fill('Updated bio as admin');
        const saveBtn = page.locator('button[name="profilesubmitbtn"]');
        assert.strictEqual(await saveBtn.count(), 1, 'Assertion Error: Admin profile save control did not render.');
        const [profileResponse] = await Promise.all([
            page.waitForResponse(response =>
                response.request().method() === 'POST' &&
                response.url().includes('home.php?mod=spacecp')
            ),
            saveBtn.click()
        ]);
        assert.ok(
            profileResponse.ok() || (profileResponse.status() >= 300 && profileResponse.status() < 400),
            `Assertion Error: Admin profile POST failed with HTTP ${profileResponse.status()}.`
        );
        const savedBio = execSync("sudo mysql -u root ultrax -N -s -e \"SELECT bio FROM pre_common_member_profile WHERE uid=1;\"").toString().trim();
        assert.strictEqual(savedBio, 'Updated bio as admin', 'Assertion Error: Admin profile bio was not persisted.');
        await page.reload({ waitUntil: 'networkidle' });
        assert.strictEqual(
            await page.locator('textarea[name="bio"]').inputValue(),
            'Updated bio as admin',
            'Assertion Error: Reloaded Admin profile UI did not show the saved bio.'
        );
        await page.screenshot({ path: 'screenshot_forum_02_admin_profile.png' });
        report += '### 2. Admin Profile Update\n- **Status**: Checked\n\n';

        console.log("Checking Admin Panel...");
        await page.goto('http://127.0.0.1:8080/admin.php');
        await page.waitForLoadState('networkidle');

        const adminPassInput = await page.$('input[name="admin_password"]');
        if (adminPassInput) {
            // mustlogin=1 authenticates this already logged-in account by password.
            await adminPassInput.fill(password);
            const adminSubmitBtn = page.locator('button[type="submit"], input[type="submit"], input[name="submit"]');
            assert.strictEqual(await adminSubmitBtn.count(), 1, 'Assertion Error: AdminCP password submit control did not render.');
            const [adminAuthResponse] = await Promise.all([
                page.waitForResponse(response =>
                    response.request().method() === 'POST' &&
                    response.url().includes('admin.php')
                ),
                adminSubmitBtn.click()
            ]);
            assert.ok(
                adminAuthResponse.ok() || (adminAuthResponse.status() >= 300 && adminAuthResponse.status() < 400),
                `Assertion Error: AdminCP authentication POST failed with HTTP ${adminAuthResponse.status()}.`
            );
            await page.waitForTimeout(1000);
        }

        // Verify admin authentication success: password prompt must be gone and admin workspace/frames loaded
        const hasLoginPrompt = await page.$('input[name="admin_password"]');
        assert.ok(!hasLoginPrompt, 'Assertion Error: Admin panel authentication failed. Still on unauthorized login screen.');
        assert.strictEqual(await page.locator('#admincpnav').count(), 1, 'Assertion Error: Authenticated AdminCP workspace did not render.');
        await page.screenshot({ path: 'screenshot_forum_03_admin_panel.png' });
        report += '### 3. Admin Panel UI\n- **Status**: Checked\n\n';

        console.log("Checking localized forum name fields...");
        await page.goto('http://127.0.0.1:8080/admin.php?action=forums&operation=edit&fid=2');
        await page.waitForLoadState('networkidle');
        const storedForumNames = JSON.parse(execSync("sudo mysql -u root ultrax -N -s -e \"SELECT name FROM pre_forum_forum WHERE fid=2;\"").toString().trim());
        for (const locale of ['SC', 'TC', 'EN']) {
            const nameInput = page.locator(`input[name="namenew[${locale}]"]`);
            assert.strictEqual(await nameInput.count(), 1, `Assertion Error: AdminCP ${locale} forum name field did not render.`);
            assert.strictEqual(
                await nameInput.inputValue(),
                storedForumNames[locale] || '',
                `Assertion Error: AdminCP ${locale} forum name field did not show the stored translation.`
            );
        }
        report += '### 4. Localized Forum Names\n- **Status**: Checked\n- **Locales**: SC, TC, EN\n\n';

        console.log("Checking AdminCP tag rename...");
        const tagRenameSuffix = Date.now().toString();
        const oldTagName = `admin_rename_${tagRenameSuffix}`;
        const name34 = `${'重'.repeat(21)}${tagRenameSuffix}`;
        const newTagName = `${'重'.repeat(22)}${tagRenameSuffix}`;
        const name36 = `${'重'.repeat(23)}${tagRenameSuffix}`;
        const taggedTid = execSync("sudo mysql -u root ultrax -N -s -e \"SELECT tid FROM pre_forum_thread ORDER BY tid LIMIT 1;\"").toString().trim();
        assert.match(taggedTid, /^\d+$/, 'Assertion Error: AdminCP tag rename test could not find a thread to associate with the tag.');
        const decoyTid = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT tid FROM pre_forum_thread WHERE tid <> ${taggedTid} ORDER BY tid LIMIT 1;"`).toString().trim();
        assert.match(decoyTid, /^\d+$/, 'Assertion Error: AdminCP tag rename test could not find a second thread for the delimiter test.');
        const decoyTagName = oldTagName.replace('_', 'X');
        const tagId = execSync(`sudo mysql -u root ultrax -N -s -e "INSERT INTO pre_common_tag (tagname, status, related_count, hot_score, created_at, updated_at) VALUES ('${oldTagName}', 0, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()); SELECT LAST_INSERT_ID();"`).toString().trim();
        assert.match(tagId, /^\d+$/, 'Assertion Error: AdminCP tag rename test could not seed a tag.');
        const taggedDoid = execSync(`sudo mysql -u root ultrax -N -s -e "INSERT INTO pre_home_doing (uid, username, dateline, body_template, body_data, message, fields) VALUES (1, 'admin', UNIX_TIMESTAMP(), '', '', '', JSON_OBJECT('tags', JSON_OBJECT('${tagId}', '${oldTagName}'))); SELECT LAST_INSERT_ID();"`).toString().trim();
        assert.match(taggedDoid, /^\d+$/, 'Assertion Error: AdminCP tag rename test could not seed a doing record.');
        execSync(`sudo mysql -u root ultrax -e "INSERT INTO pre_common_tagitem (tagid, itemid, idtype, created_at) VALUES (${tagId}, ${taggedTid}, 'tid', UNIX_TIMESTAMP()), (${tagId}, ${taggedDoid}, 'doid', UNIX_TIMESTAMP()); UPDATE pre_forum_thread SET tags=CONCAT(tags, '${tagId},${oldTagName}', CHAR(9)) WHERE tid=${taggedTid}; UPDATE pre_forum_thread SET tags=CONCAT(tags, '${tagId},${decoyTagName}', CHAR(9)) WHERE tid=${decoyTid};"`);

        const renameTagViaAdmin = async (fromName, toName) => {
            await page.goto(`http://127.0.0.1:8080/admin.php?action=tag&operation=admin&searchsubmit=yes&perpage=20&tagname=${encodeURIComponent(fromName)}`);
            await page.waitForLoadState('networkidle');
            const tagRenameForm = page.locator('form[action*="action=tag"]');
            assert.strictEqual(await tagRenameForm.count(), 1, 'Assertion Error: AdminCP tag management form did not render.');
            const seededTagCheckbox = tagRenameForm.locator(`input[name="tagidarray[]"][value="${tagId}"]`);
            assert.strictEqual(await seededTagCheckbox.count(), 1, 'Assertion Error: Seeded tag did not render in AdminCP tag results.');
            await seededTagCheckbox.check();
            const renameOperation = tagRenameForm.locator('input[name="operate_type"][value="rename"]');
            assert.strictEqual(await renameOperation.count(), 1, 'Assertion Error: AdminCP tag rename operation did not render.');
            await renameOperation.check();
            await tagRenameForm.locator('input[name="renametag"]').fill(toName);
            const tagRenameSubmit = tagRenameForm.locator('input[name="submit"], button[name="submit"]');
            assert.strictEqual(await tagRenameSubmit.count(), 1, 'Assertion Error: AdminCP tag rename submit control did not render.');
            const [tagRenameResponse] = await Promise.all([
                page.waitForResponse(response =>
                    response.request().method() === 'POST' &&
                    response.url().includes('admin.php?action=tag&operation=admin')
                ),
                tagRenameSubmit.click()
            ]);
            assert.ok(
                tagRenameResponse.ok() || (tagRenameResponse.status() >= 300 && tagRenameResponse.status() < 400),
                `Assertion Error: AdminCP tag rename POST failed with HTTP ${tagRenameResponse.status()}.`
            );
        };

        await renameTagViaAdmin(oldTagName, name34);
        await renameTagViaAdmin(name34, newTagName);
        await renameTagViaAdmin(newTagName, name36);
        await page.locator('.infotitle3').waitFor({state: 'visible'});
        const invalidTagMessage = await page.locator('.infotitle3').textContent();
        assert.ok(
            /2 to 35|2\s*至\s*35/.test(invalidTagMessage),
            'Assertion Error: AdminCP did not show the localized 35-character tag limit.'
        );
        const renamedTag = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT tagid, tagname FROM pre_common_tag WHERE tagid=${tagId};"`).toString().trim();
        assert.strictEqual(renamedTag, `${tagId}\t${newTagName}`, 'Assertion Error: AdminCP tag rename did not preserve the tag ID and update its name.');
        const renamedThreadReference = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT COUNT(*) FROM pre_forum_thread WHERE tid=${taggedTid} AND tags LIKE CONCAT('%', '${tagId},${newTagName}', CHAR(9), '%');"`).toString().trim();
        assert.strictEqual(renamedThreadReference, '1', 'Assertion Error: AdminCP tag rename did not update the thread tag reference.');
        const retainedDecoyReference = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT COUNT(*) FROM pre_forum_thread WHERE tid=${decoyTid} AND tags LIKE CONCAT('%', '${tagId},${decoyTagName}', CHAR(9), '%');"`).toString().trim();
        assert.strictEqual(retainedDecoyReference, '1', 'Assertion Error: AdminCP tag rename changed an unrelated delimiter-containing tag reference.');
        const retainedTagItem = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT COUNT(*) FROM pre_common_tagitem WHERE tagid=${tagId} AND itemid=${taggedTid} AND idtype='tid';"`).toString().trim();
        assert.strictEqual(retainedTagItem, '1', 'Assertion Error: AdminCP tag rename did not preserve the tag association.');
        const renamedDoingFields = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT fields FROM pre_home_doing WHERE doid=${taggedDoid};"`).toString().trim();
        assert.ok(renamedDoingFields.includes(`\"${tagId}\":\"${newTagName}\"`), 'Assertion Error: AdminCP tag rename did not update the doing tag reference.');
        const retainedDoingTagItem = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT COUNT(*) FROM pre_common_tagitem WHERE tagid=${tagId} AND itemid=${taggedDoid} AND idtype='doid';"`).toString().trim();
        assert.strictEqual(retainedDoingTagItem, '1', 'Assertion Error: AdminCP tag rename did not preserve the doing tag association.');
        execSync(`sudo mysql -u root ultrax -e "UPDATE pre_forum_thread SET tags=REPLACE(tags, '${tagId},${newTagName}', '') WHERE tid=${taggedTid}; UPDATE pre_forum_thread SET tags=REPLACE(tags, '${tagId},${decoyTagName}', '') WHERE tid=${decoyTid}; DELETE FROM pre_home_doing WHERE doid=${taggedDoid}; DELETE FROM pre_common_tagitem WHERE tagid=${tagId}; DELETE FROM pre_common_tag WHERE tagid=${tagId};"`);
        report += '### 5. AdminCP Tag Rename\n- **Status**: Checked\n- **Tag ID Preserved**: Yes\n- **Thread and Doing References Updated**: Yes\n\n';

        console.log("Checking Admin Panel Logs Page...");
        await page.goto('http://127.0.0.1:8080/admin.php?action=logs&operation=cp');
        await page.waitForLoadState('networkidle');

        const logSearchForm = page.locator('#logsearchform');
        assert.strictEqual(await logSearchForm.count(), 1, 'Assertion Error: AdminCP operation-log search form did not render.');
        const logSearchAction = await logSearchForm.getAttribute('action');
        assert.ok(logSearchAction && /(?:^|[?&])action=logs(?:&|$)/.test(logSearchAction), 'Assertion Error: AdminCP log form did not target the logs action.');
        assert.ok(logSearchAction && /(?:^|[?&])operation=cp(?:&|$)/.test(logSearchAction), 'Assertion Error: AdminCP log form did not render the operation-log view.');
        assert.strictEqual(await logSearchForm.locator('#keywordraw').count(), 1, 'Assertion Error: AdminCP operation-log keyword field did not render.');

        const likeSearchKeyword = 'literal-ci\\%_marker';
        const likeSearchData = Buffer.from(JSON.stringify({ action: 'ci_like_test', extralog: likeSearchKeyword })).toString('base64');
        execSync(`sudo mysql -u root ultrax -e "INSERT INTO pre_common_log (uid, loginname, username, type, data, operationuid, source, device, record, dateline) VALUES (1, 'admin', 'admin', 'cp', FROM_BASE64('${likeSearchData}'), 1, 'CI', '{}', '', UNIX_TIMESTAMP());"`);
        try {
            await logSearchForm.locator('#keywordraw').fill(likeSearchKeyword);
            await Promise.all([
                page.waitForURL(url => url.includes('action=logs') && url.includes('keywordenc=')),
                logSearchForm.locator('#keywordraw').press('Enter')
            ]);
            assert.ok((await page.locator('body').textContent()).includes(likeSearchKeyword), 'Assertion Error: AdminCP log search did not match literal backslash, percent, and underscore characters.');
        } finally {
            execSync(`sudo mysql -u root ultrax -e "DELETE FROM pre_common_log WHERE type='cp' AND data LIKE '%ci_like_test%' AND source='CI';"`);
        }
        await page.screenshot({ path: 'screenshot_forum_04_admin_logs.png' });
        report += '### 6. Admin Panel Logs Access\n- **Status**: Checked\n- **URL**: admin.php?action=logs&operation=cp\n\n';

        console.log("Checking renamed uploader operations...");
        await page.goto('http://127.0.0.1:8080/forum.php?mod=post&action=newthread&fid=2');
        await page.waitForLoadState('networkidle');
        const uploadFormhash = await page.evaluate(() => window.FORMHASH || document.querySelector('input[name="formhash"]')?.value || '');
        assert.ok(uploadFormhash, 'Assertion Error: Uploader operation test could not obtain formhash.');

        const validJpegBase64 = fs.readFileSync('static/image/smiley/BQ2/alu1.jpg').toString('base64');
        const uploadOperation = async (operation, fields = {}, query = '') => {
            return await page.evaluate(async ({ op, q, f, b64 }) => {
                const formhash = window.FORMHASH || (document.querySelector('input[name="formhash"]') ? document.querySelector('input[name="formhash"]').value : '');
                const blob = await fetch('data:image/jpeg;base64,' + b64).then(r => r.blob());
                const formData = new FormData();
                formData.append('formhash', formhash);
                for (const [k, v] of Object.entries(f)) {
                    formData.append(k, v);
                }
                formData.append('Filedata', blob, op + '_test.jpg');
                const res = await fetch(`misc.php?mod=upload&operation=${op}${q}`, {
                    method: 'POST',
                    body: formData
                });
                return await res.text();
            }, { op: operation, q: query, f: fields, b64: validJpegBase64 });
        };

        const forumUpload = await uploadOperation('upload', {}, '&simple=1&type=image&fid=2');
        assert.match(forumUpload.trim(), /^DISCUZUPLOAD\|0\|\d+\|1\|0$/, `Assertion Error: Standard forum image upload failed: ${forumUpload}`);

        const pollUpload = JSON.parse(await uploadOperation('poll', {}, '&fid=2'));
        assert.ok(pollUpload.aid > 0 && pollUpload.errorcode === 0, `Assertion Error: Poll image upload failed: ${JSON.stringify(pollUpload)}`);

        const albumUpload = JSON.parse(await uploadOperation('album'));
        assert.ok(parseInt(albumUpload.picid, 10) > 0, `Assertion Error: Album image upload failed: ${JSON.stringify(albumUpload)}`);

        const portalCatid = execSync("sudo mysql -u root ultrax -N -s -e \"SELECT catid FROM pre_portal_category ORDER BY catid LIMIT 1;\"").toString().trim();
        assert.match(portalCatid, /^\d+$/, 'Assertion Error: No portal category exists for the portal upload test.');
        const portalUpload = JSON.parse(await uploadOperation('portal', { catid: portalCatid, aid: '0' }));
        assert.ok(portalUpload.aid > 0 && portalUpload.errorcode === 0, `Assertion Error: Portal attachment upload failed: ${JSON.stringify(portalUpload)}`);

        const jsonEditorUpload = JSON.parse(await uploadOperation('jsoneditorupload', {}, '&fid=2'));
        assert.strictEqual(
            jsonEditorUpload.success, 0,
            `Assertion Error: JSON editor upload endpoint should reject uploads in plain mode: ${JSON.stringify(jsonEditorUpload)}`
        );

        report += '### 7. Uploader Endpoint Contracts in Plain Mode\n- **Status**: Checked\n- **Forum Image Endpoint**: Success\n- **Poll Image Endpoint**: Success\n- **Album Image Endpoint**: Success\n- **Portal Attachment Endpoint**: Success\n- **JSON Editor Endpoint Protection**: Verified (returns success=0)\n\n';

    } catch (error) {
        console.error("Admin test execution failed:", error);
        process.exitCode = 1;
        console.log('::error::' + String(error && error.message || error).slice(0, 1000).replace(/[\r\n]+/g, ' | '));
        const failBody = ('**FAIL ' + (process.env.TEST_RUN_ID || 'ci') + ' admin**\n\n```\n' + String(error.stack || error.message).slice(0, 50000) + '\n```').slice(0, 60000);
        await reportCiFailure({ label: 'admin', body: failBody });
        try {
            const currentUrl = page.url();
            const pageTitle = await page.title().catch(() => 'Unknown Title');
            const pageSource = await page.content().catch(() => '');
            if (pageSource) {
                fs.writeFileSync('admin_page_source.html', pageSource);
                fs.writeFileSync('browser_page_source.html', pageSource);
            }
            await page.screenshot({ path: 'screenshot_admin_failure.png', fullPage: true }).catch(() => {});
            const errLog = `[Admin Failure] URL: ${currentUrl} | Title: ${pageTitle}\nError: ${error.stack || error.message}\nPage Source saved to admin_page_source.html\n---\n`;
            fs.appendFileSync('browser_error.txt', errLog);
        } catch (e) {
            console.error('Failed to capture failure state:', e.message);
        }
        report += "## Error Encountered in Admin Test\n```\n" + error.message + "\n```\n\n";
    } finally {
        await browser.close();
        fs.appendFileSync('functional_test_report.md', report);
        console.log("Admin tests completed and report appended.");
    }
})();
