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
    page.on('pageerror', error => browserErrors.push(error.message));
    page.on('console', message => {
        if(message.type() === 'error') {
            browserErrors.push(message.text());
        }
    });
    let report = '\n\n## Mobile Registration Functional Test Report\n\n';

    try {
        const suffix = Date.now().toString().slice(-8);
        const username = `m${suffix}`;
        const email = `${username}@example.com`;
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

        await page.evaluate(({ username, email, password }) => {
            const form = document.getElementById('registerform');
            const textInputs = form.querySelectorAll('input[type="text"]');
            const passwordInputs = form.querySelectorAll('input[type="password"]');
            const emailInput = form.querySelector('input[type="email"], input[name*="email"]');
            if (textInputs[0]) textInputs[0].value = username;
            if (passwordInputs[0]) passwordInputs[0].value = password;
            if (passwordInputs[1]) passwordInputs[1].value = password;
            if (emailInput) emailInput.value = email;
            const secqaa = form.querySelector('input[name*="secanswer"]');
            if (secqaa) secqaa.value = '2';
            form.submit();
        }, { username, email, password });

        await page.waitForNavigation({ waitUntil: 'networkidle' }).catch(() => {});
        await page.waitForTimeout(1500);

        const memberCount = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT COUNT(*) FROM pre_common_member WHERE username='${username}';"`).toString().trim();
        assert.strictEqual(memberCount, '1', 'Assertion Error: Mobile registration did not create the member.');

        await page.goto('http://127.0.0.1:8080/home.php?mod=spacecp');
        await page.waitForLoadState('networkidle');
        assert.ok(await page.$('.header'), 'Assertion Error: Authenticated mobile page did not render the touch header.');
        assert.ok((await page.textContent('body')).includes(username), 'Assertion Error: Mobile registration did not establish a logged-in session.');

        const dbScalar = sql => execSync(`sudo mysql -u root ultrax -N -s -e "${sql}"`).toString().trim();
        const waitForDbValue = async (sql, expected, message) => {
            for(let attempt = 0; attempt < 15; attempt++) {
                if(dbScalar(sql) === expected) {
                    return;
                }
                await page.waitForTimeout(500);
            }
            assert.fail(`${message}. Found: ${dbScalar(sql)}`);
        };
        const subject = `Mobile thread ${suffix}`;
        const message = `Mobile thread body ${suffix}.`;
        const reply = `Mobile reply ${suffix}.`;
        const editedReply = `Mobile reply edited ${suffix}.`;
        const imagePath = 'mobile_test_image.png';
        fs.writeFileSync(imagePath, Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAoAAAAKCAYAAACNMs+9AAAAEUlEQVR42mP8z8AARDAEg4gAAH8YAwE8j7i4AAAAAElFTkSuQmCC', 'base64'));

        console.log('Posting mobile thread with image attachment...');
        await page.goto('http://127.0.0.1:8080/forum.php?mod=post&action=newthread&fid=2');
        await page.waitForLoadState('networkidle');
        assert.ok(await page.$('#postform #needsubject'), 'Assertion Error: Mobile new-thread form did not render.');
        await page.locator('#needsubject').fill(subject);
        await page.locator('#needmessage').fill(message);
        const imageInput = page.locator('#filedata');
        assert.strictEqual(await imageInput.count(), 1, 'Assertion Error: Mobile image upload control did not render.');
        const uploadResponse = page.waitForResponse(response => response.request().method() === 'POST' && response.url().includes('misc.php?mod=swfupload'));
        await imageInput.setInputFiles(imagePath);
        const uploadText = await (await uploadResponse).text();
        assert.match(uploadText, /^DISCUZUPLOAD\|1\|0\|\d+\|1\|/, `Assertion Error: Mobile image upload failed. Response: ${uploadText}`);
        await page.waitForFunction(() => document.querySelector('#imglist input[name^="attachnew["]'), { timeout: 5000 }).catch(async () => {
            const uploadListHtml = await page.$eval('#imglist', element => element.innerHTML).catch(() => 'missing');
            throw new assert.AssertionError({
                message: `Assertion Error: Mobile upload did not append attachnew. Response: ${uploadText}; imglist=${uploadListHtml}; errors=${browserErrors.join(' | ') || 'none'}`,
            });
        });
        const aid = await page.locator('#imglist input[name^="attachnew["]').evaluate(input => input.name.match(/^attachnew\[(\d+)\]/)[1]);
        await page.locator('#needmessage').fill(`${message} [attachimg]${aid}[/attachimg]`);
        await page.waitForTimeout(250);
        await page.locator('#postsubmit').click();
        await waitForDbValue(`SELECT COUNT(*) FROM pre_forum_thread WHERE subject='${subject}'`, '1', 'Assertion Error: Mobile thread was not created');
        const tid = dbScalar(`SELECT tid FROM pre_forum_thread WHERE subject='${subject}' ORDER BY tid DESC LIMIT 1`);
        assert.ok(tid, 'Assertion Error: Mobile thread ID was not found.');
        const attachmentIndex = dbScalar(`SELECT CONCAT(tid, ':', tableid) FROM pre_forum_attachment WHERE aid='${aid}' LIMIT 1`);
        const tableid = attachmentIndex.split(':')[1];
        const isimage = tableid === undefined ? '' : dbScalar(`SELECT isimage FROM pre_forum_attachment_${tableid} WHERE aid='${aid}' AND tid='${tid}' LIMIT 1`);
        assert.strictEqual(attachmentIndex, `${tid}:${tid.slice(-1)}`, 'Assertion Error: Mobile image attachment was not linked to its thread.');
        assert.strictEqual(isimage, '1', 'Assertion Error: Mobile image upload was not stored as an image.');

        console.log('Replying to mobile thread...');
        await page.goto(`http://127.0.0.1:8080/forum.php?mod=post&action=reply&fid=2&tid=${tid}`);
        await page.waitForLoadState('networkidle');
        assert.ok(await page.$('#postform #needmessage'), 'Assertion Error: Mobile reply form did not render.');
        await page.locator('#needmessage').fill(reply);
        await page.waitForTimeout(250);
        await page.locator('#postsubmit').click();
        await waitForDbValue(`SELECT COUNT(*) FROM pre_forum_post WHERE tid='${tid}' AND message='${reply}'`, '1', 'Assertion Error: Mobile reply was not created');
        const replyPid = dbScalar(`SELECT pid FROM pre_forum_post WHERE tid='${tid}' AND message='${reply}' ORDER BY pid DESC LIMIT 1`);
        assert.ok(replyPid, 'Assertion Error: Mobile reply ID was not found.');

        console.log('Editing mobile reply...');
        await page.goto(`http://127.0.0.1:8080/forum.php?mod=post&action=edit&fid=2&tid=${tid}&pid=${replyPid}`);
        await page.waitForLoadState('networkidle');
        assert.ok(await page.$('#postform #needmessage'), 'Assertion Error: Mobile edit form did not render.');
        await page.locator('#needmessage').fill(editedReply);
        await page.locator('#postsubmit').click();
        await waitForDbValue(`SELECT message FROM pre_forum_post WHERE pid='${replyPid}'`, editedReply, 'Assertion Error: Mobile reply edit was not saved');

        await page.goto(`http://127.0.0.1:8080/forum.php?mod=viewthread&tid=${tid}`);
        await page.waitForLoadState('networkidle');
        assert.ok((await page.textContent('body')).includes(editedReply), 'Assertion Error: Edited mobile reply was not rendered in the thread.');
        await page.screenshot({ path: 'screenshot_mobile_register.png' });
        report += `### Touch Registration, Posting, Replying and Editing\n- **Status**: Checked\n- **Username**: ${username}\n- **Thread**: ${tid}\n- **Reply**: ${replyPid}\n- **Image Attachment**: ${aid}\n- **Screenshot**: \`screenshot_mobile_register.png\`\n\n`;
    } catch(error) {
        console.error('Test execution failed:', error);
        process.exitCode = 1;
        report += `## Error Encountered\n\`\`\`\n${error.message}\n\`\`\`\n\n`;
    } finally {
        await browser.close();
        if(fs.existsSync('mobile_test_image.png')) {
            fs.unlinkSync('mobile_test_image.png');
        }
        fs.appendFileSync('functional_test_report.md', report);
        console.log('Mobile registration tests completed and report appended.');
    }
})();
