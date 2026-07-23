const { chromium } = require('playwright');
const crypto = require('crypto');
const fs = require('fs');
const assert = require('assert');
const { execSync } = require('child_process');

(async () => {
    const browser = await chromium.launch();
    fs.writeFileSync('configure_mobile_test.php', `<?php
require './source/class/class_core.php';
$discuz = C::app();
$discuz->init();
C::t('common_setting')->update('styleid2', '1');
require_once libfile('function/cache');
updatecache('setting');
`);
    execSync('php configure_mobile_test.php');
    fs.unlinkSync('configure_mobile_test.php');
    const context = await browser.newContext({
        viewport: { width: 390, height: 844 },
        userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Version/17.0 Mobile/15E148 Safari/604.1',
    });
    const cookieSalt = crypto.createHash('md5').update('/|').digest('hex').slice(0, 4);
    await context.addCookies([
        { name: `discuz_${cookieSalt}_mobile`, value: '2', url: 'http://127.0.0.1:8080' },
    ]);
    const page = await context.newPage();
    let report = '\n\n## Mobile Registration Functional Test Report\n\n';

    try {
        const suffix = Date.now().toString().slice(-8);
        const username = `m${suffix}`;
        const email = `${username}@example.com`;
        const password = 'Testpassword123!';

        console.log('Opening mobile registration...');
        await page.goto('http://127.0.0.1:8080/member.php?mod=register');
        await page.waitForLoadState('networkidle');
        assert.ok(await page.$('.header_toplogo'), 'Assertion Error: Mobile registration did not render the touch template.');
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

        await page.screenshot({ path: 'screenshot_mobile_register.png' });
        report += `### Touch Registration\n- **Status**: Checked\n- **Username**: ${username}\n- **Screenshot**: \`screenshot_mobile_register.png\`\n\n`;
    } catch(error) {
        console.error('Test execution failed:', error);
        process.exitCode = 1;
        report += `## Error Encountered\n\`\`\`\n${error.message}\n\`\`\`\n\n`;
    } finally {
        await browser.close();
        fs.appendFileSync('functional_test_report.md', report);
        console.log('Mobile registration tests completed and report appended.');
    }
})();
