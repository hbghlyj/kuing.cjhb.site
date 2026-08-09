const { chromium } = require('playwright');
const fs = require('fs');
const assert = require('assert');
const { reportCiFailure } = require('./report_ci_failure');
const testRunId = process.env.TEST_RUN_ID || Date.now().toString();

(async () => {
    const browser = await chromium.launch();
    const context = await browser.newContext();
    const page = await context.newPage();
    page.on('pageerror', error => {
        throw new Error(`Uncaught browser exception at ${page.url()}: ${error.message}`);
    });
    page.on('console', message => {
        if(message.type() === 'error') {
            throw new Error(`Console error in browser: ${message.text()}`);
        }
    });
    page.on('requestfailed', request => {
        throw new Error(`Browser request failed: ${request.url()} (${request.failure()?.errorText || 'unknown error'})`);
    });
    let report = '\n\n## Forum Search Functional Test Report\n\n';

    try {
        const existingSubject = `Thread with Attachment ${testRunId}`;

        console.log('Checking guest search form...');
        await page.goto('http://127.0.0.1:8080/search.php?mod=forum');
        await page.waitForLoadState('networkidle');
        const searchForm = page.locator('form.searchform[action*="search.php"]');
        const advancedSearchForm = page.locator('.bm_c > form[action*="search.php"]');
        const keywordInput = searchForm.locator('#scform_srchtxt');
        const submitButton = searchForm.locator('#scform_submit');
        assert.strictEqual(await searchForm.count(), 1, 'Assertion Error: Forum quick-search form did not render for a guest.');
        assert.strictEqual(await advancedSearchForm.count(), 1, 'Assertion Error: Forum advanced-search form did not render for a guest.');
        assert.strictEqual(await keywordInput.count(), 1, 'Assertion Error: Forum search keyword field did not render.');
        assert.strictEqual(await submitButton.count(), 1, 'Assertion Error: Forum search submit control did not render.');

        console.log(`Searching for existing thread "${existingSubject}" through the guest form...`);
        await keywordInput.fill(existingSubject);
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle' }),
            submitButton.click()
        ]);
        assert.match(page.url(), /search\.php\?mod=forum.*searchid=\d+/, 'Assertion Error: Forum search submission did not navigate to a result set.');
        const resultLink = page.locator('a[href*="mod=viewthread"]').filter({ hasText: existingSubject });
        assert.strictEqual(await resultLink.count(), 1, `Assertion Error: Forum search did not render exactly one link to "${existingSubject}".`);

        await page.screenshot({ path: 'screenshot_search_result.png' });
        report += `### Guest Forum Search\n- **Status**: Passed\n- **Keyword**: \`${existingSubject}\`\n- **Screenshot**: \`screenshot_search_result.png\`\n\n`;
    } catch(error) {
        console.error('Test execution failed:', error);
        process.exitCode = 1;
        console.log('::error::' + String(error && error.message || error).slice(0, 1000).replace(/[\r\n]+/g, ' | '));
        const failBody = ('**FAIL ' + (process.env.TEST_RUN_ID || 'ci') + ' search**\n\n```\n' + String(error.stack || error.message).slice(0, 50000) + '\n```').slice(0, 60000);
        await reportCiFailure({ label: 'search', body: failBody });
        report += `## Error Encountered\n\`\`\`\n${error.message}\n\`\`\`\n\n`;
    } finally {
        await browser.close();
        fs.appendFileSync('functional_test_report.md', report);
        console.log('Forum search tests completed and report appended.');
    }
})();
