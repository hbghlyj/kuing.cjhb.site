const API_ROOT = 'https://api.github.com/repos/';

async function reportCiFailure({ label, body }) {
    const token = process.env.GITHUB_TOKEN || process.env.GH_TOKEN;
    if (!token) return;

    const repository = process.env.GITHUB_REPOSITORY || 'hbghlyj/kuing.cjhb.site';
    const ref = process.env.GITHUB_REF || '';
    const prNum = ((ref.match(/^refs\/pull\/(\d+)\//) || [])[1]);
    const headers = {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/vnd.github.v3+json'
    };
    let endpoint;
    let payload;
    let resultType;

    if (prNum) {
        endpoint = `${API_ROOT}${repository}/pulls/${prNum}/reviews`;
        payload = { body, event: 'COMMENT' };
        resultType = 'review';
    } else if (process.env.GITHUB_SHA) {
        endpoint = `${API_ROOT}${repository}/commits/${encodeURIComponent(process.env.GITHUB_SHA)}/comments`;
        payload = { body };
        resultType = 'commit comment';
    } else {
        console.log(`Skipping ${label} failure report: no pull request or commit ref.`);
        return;
    }

    try {
        const response = await fetch(endpoint, {
            method: 'POST',
            headers,
            body: JSON.stringify(payload)
        });
        const result = await response.json();
        if (!response.ok) {
            console.log(`${resultType} error: ${JSON.stringify(result)}`);
        } else {
            console.log(`${resultType.toUpperCase().replace(/ /g, '_')}_CREATED ${result.html_url || JSON.stringify(result)}`);
        }
    } catch (error) {
        console.log(`${resultType} error`, error.message);
    }
}

module.exports = { reportCiFailure };
