const { chromium } = require('playwright');
const fs = require('fs');
const assert = require('assert');
const { execSync } = require('child_process');

(async () => {
    const browser = await chromium.launch();
    const context = await browser.newContext();
    const page = await context.newPage();
    let report = '\n\n## Forum Search Functional Test Report\n\n';

    try {
        const keyword = `PlaywrightSearch${Date.now()}`;
        const subject = `${keyword} searchable thread`;

        console.log('Creating searchable thread...');
        execSync(`sudo mysql -u root ultrax -e "INSERT INTO pre_forum_thread (fid, author, authorid, subject, dateline, lastpost, lastposter) VALUES (2, 'admin', 1, '${subject}', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'admin');"`);

        console.log('Checking guest search form...');
        await page.goto('http://127.0.0.1:8080/search.php?mod=forum');
        await page.waitForLoadState('networkidle');
        assert.ok(await page.$('input[name="srchtxt"]'), 'Assertion Error: Forum search form did not render for a guest.');

        console.log('Searching for the seeded thread...');
        await page.goto(`http://127.0.0.1:8080/search.php?mod=forum&searchsubmit=yes&srchtype=title&srchtxt=${encodeURIComponent(keyword)}`);
        await page.waitForLoadState('networkidle');
        const body = await page.textContent('body');
        assert.ok(body.includes(subject), 'Assertion Error: Forum search did not return the seeded thread.');

        await page.screenshot({ path: 'screenshot_search_forum.png' });
        report += `### Guest Forum Search\n- **Status**: Checked\n- **Keyword**: \`${keyword}\`\n- **Screenshot**: \`screenshot_search_forum.png\`\n\n`;
    } catch(error) {
        console.error('Test execution failed:', error);
        process.exitCode = 1;
        report += `## Error Encountered\n\`\`\`\n${error.message}\n\`\`\`\n\n`;
    } finally {
        await browser.close();
        fs.appendFileSync('functional_test_report.md', report);
        console.log('Forum search tests completed and report appended.');
    }
})();
