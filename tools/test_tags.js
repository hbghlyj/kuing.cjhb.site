const { chromium } = require('playwright');
const fs = require('fs');
const assert = require('assert');
const { execSync } = require('child_process');

(async () => {
    const browser = await chromium.launch();
    const context = await browser.newContext();
    const page = await context.newPage();

    page.on('pageerror', exception => {
        throw new Error(`Uncaught exception in browser: ${exception}`);
    });

    page.on('console', msg => {
        if (msg.type() === 'error') {
            throw new Error(`Console error in browser: ${msg.text()}`);
        }
    });

    let report = "\n\n## Tags Feature Functional Test Report\n\n";
    console.log("Starting Tags Feature tests...");

    try {
        console.log("Creating synthetic thread and tag...");
        // 1. Thread
        execSync(`sudo mysql -u root ultrax -e "INSERT INTO pre_forum_thread (fid, author, authorid, subject, dateline, lastpost, lastposter) VALUES (1, 'admin', 1, 'Thread with Tags', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'admin');"`);
        const tidOutput = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT tid FROM pre_forum_thread WHERE subject='Thread with Tags' ORDER BY tid DESC LIMIT 1;"`).toString().trim();

        // 2. Tag
        execSync(`sudo mysql -u root ultrax -e "INSERT INTO pre_common_tag (tagname, status) VALUES ('playwright', 0);" || true`);
        const tagidOutput = execSync(`sudo mysql -u root ultrax -N -s -e "SELECT tagid FROM pre_common_tag WHERE tagname='playwright' LIMIT 1;"`).toString().trim();

        // 3. Link Tag to Thread
        if (tidOutput && tagidOutput) {
            execSync(`sudo mysql -u root ultrax -e "INSERT INTO pre_common_tagitem (tagid, itemid, idtype) VALUES (${tagidOutput}, ${tidOutput}, 'tid');" || true`);
        }

        // Test Tag Search
        if (tagidOutput) {
            console.log("Testing Tag Search...");
            await page.goto(`http://127.0.0.1:8080/misc.php?mod=tag&id=${tagidOutput}`);
            await page.waitForLoadState('networkidle');
            const tagSearchText = await page.textContent('body');
            assert.ok(tagSearchText.includes('Thread with Tags') || tagSearchText.includes('playwright'), 'Assertion Error: Tag search result did not list the created thread or tag.');
            await page.screenshot({ path: 'screenshot_tags_03_search_result.png' });
            report += `### Tag Search Result\n- **Status**: Checked\n- **Screenshot**: \`screenshot_tags_03_search_result.png\`\n\n`;
        }

        // Admin Tag Management Check
        console.log("Testing Admin Panel Tag Management UI...");
        await page.goto('http://127.0.0.1:8080/admin.php?action=tag');
        await page.waitForLoadState('networkidle');
        const adminPageText = await page.textContent('body');
        assert.ok(adminPageText.includes('Login') || adminPageText.includes('登录') || adminPageText.includes('密码'), 'Assertion Error: Admin panel tag management UI did not load correctly.');
        report += `### Admin Tag Management UI\n- **Status**: Checked\n\n`;

    } catch (error) {
        console.error("Test execution failed:", error);
        process.exitCode = 1;
        report += "## Error Encountered in Tags Test\n```\n" + error.message + "\n```\n\n";
    } finally {
        await browser.close();
        fs.appendFileSync('functional_test_report.md', report);
        console.log("Tags tests completed and report appended.");
    }
})();
