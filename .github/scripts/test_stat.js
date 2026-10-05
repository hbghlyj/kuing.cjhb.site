const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true, ...(process.env.TEST_CHROME_CHANNEL ? { channel: process.env.TEST_CHROME_CHANNEL } : {}) });
    try {
        const page = await browser.newPage();
        await page.setContent('<div class="stat-page"><div id="chart" style="width:600px;height:300px"></div><div id="gauge" style="width:600px;height:300px"></div></div>');
        await page.addScriptTag({ path: path.resolve('static/js/echarts/echarts.common.min.js') });
        await page.evaluate(() => {
            window.$ = id => document.getElementById(id);
            window.STAT_LABELS = { noData: 'No data fixture' };
            window.$L = key => key;
            window.hostconvert = url => url;
            window.isUndefined = value => typeof value === 'undefined';
            // Mock only transport; use common.js's real Ajax response parser.
            window.XMLHttpRequest = function() {
                this.open = () => {};
                this.setRequestHeader = () => {};
                this.send = () => {
                    this.responseText = JSON.stringify(window.fixture);
                    this.readyState = 4;
                    this.status = 200;
                    this.onreadystatechange();
                };
            };
        });
        const commonSource = fs.readFileSync('static/js/common.js', 'utf8');
        await page.addScriptTag({ content: commonSource.slice(commonSource.indexOf('function Ajax('), commonSource.indexOf('function getHost(')) });
        await page.addScriptTag({ path: path.resolve('static/js/stat.js') });
        const result = await page.evaluate(() => {
            window.fixture = { xaxis: ['2026-10-01', '2026-10-02'], graphs: [{ title: 'Posts', data: ['2', '5'] }] };
            drawstatchart('/fixture', 300, null, $('chart'));
            const option = echarts.getInstanceByDom($('chart')).getOption();
            const supportsGauge = statSupports('gauge');
            statGauge($('gauge'), 1500);
            const gauge = echarts.getInstanceByDom($('gauge')).getOption();
            window.fixture = { xaxis: [], graphs: [] };
            drawstatchart('/empty', 300, null, $('chart'));
            const empty = echarts.getInstanceByDom($('chart')).getOption();
            return { dates: option.xAxis[0].data, values: option.series[0].data, supportsGauge, gaugeType: gauge.series[0].type, emptyTitle: empty.title[0].text, emptySeries: empty.series.length };
        });
        assert.deepEqual(result.dates, ['2026-10-01', '2026-10-02']);
        assert.deepEqual(result.values, [2, 5]);
        assert.equal(result.supportsGauge, false, 'Bundled ECharts must fall back when gauge is unsupported');
        assert.equal(result.gaugeType, 'pie');
        assert.equal(result.emptyTitle, 'No data fixture');
        assert.equal(result.emptySeries, 0, 'Old series must not survive an empty response');
        const memberTemplate = fs.readFileSync('template/default/forum/stat_memberlist.htm', 'utf8');
        assert.ok(memberTemplate.includes('stat-ava-uid'), 'Compact UID beneath the username must remain');
        assert.ok(memberTemplate.includes('order=uid'), 'UID sorting must remain available');
        assert.ok(!memberTemplate.includes('<span class="stat-cell-w2">$member[uid]</span>'), 'Redundant UID column must not consume width');
        for (const file of ['stat_main', 'stat_memberlist', 'stat_team', 'stat_trade', 'stat_misc', 'stat_misc_export']) {
            const source = fs.readFileSync(`template/default/forum/${file}.htm`, 'utf8');
            const stack = [];
            for (const match of source.matchAll(/\{(if\s|loop\s|\/if|\/loop)/g)) {
                const tag = match[1].trim();
                if (tag.startsWith('/')) assert.equal(stack.pop(), tag.slice(1), `${file}: unmatched ${tag}`);
                else stack.push(tag);
            }
            assert.equal(stack.length, 0, `${file}: unclosed template conditions`);
            assert.ok(!/calendar\.js|showcalendar|type="text\/javascript"/.test(source));
        }
        console.log('Statistics JSON charts, gauge fallback, empty state and template checks passed');
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
