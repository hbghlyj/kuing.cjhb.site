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
        console.log('Finding existing thread for search test...');
        const existingSubject = execSync("sudo mysql -u root ultrax -N -s -e \"SELECT subject FROM pre_forum_thread WHERE displayorder >= 0 ORDER BY tid DESC LIMIT 1;\"").toString().trim() || 'Thread with Attachment';
        const keyword = existingSubject.split(' ')[0] || existingSubject;

        console.log('Checking guest search form...');
        await page.goto('http://127.0.0.1:8080/search.php?mod=forum');
        await page.waitForLoadState('networkidle');
        assert.ok(await page.$('input[name="srchtxt"]'), 'Assertion Error: Forum search form did not render for a guest.');

        console.log(`Searching for existing thread "${existingSubject}" with keyword "${keyword}"...`);
        await page.goto(`http://127.0.0.1:8080/search.php?mod=forum&searchsubmit=yes&srchtype=title&srchtxt=${encodeURIComponent(keyword)}`);
        await page.waitForLoadState('networkidle');
        const body = await page.textContent('body');
        assert.ok(body.includes(existingSubject) || body.includes(keyword), `Assertion Error: Forum search did not return existing thread "${existingSubject}".`);

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
