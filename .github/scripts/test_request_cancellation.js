const assert = require('assert');
const { EventEmitter } = require('events');
const { trackDocumentRequests } = require('./request_cancellation');

const page = new EventEmitter();
let closed = false;
let detached = false;
page.isClosed = () => closed;
const frame = { isDetached: () => detached };
const ignore = trackDocumentRequests(page);
function request(navigation = false, error = 'net::ERR_ABORTED', owner = frame) {
    const result = {
        frame: () => owner,
        isNavigationRequest: () => navigation,
        failure: () => ({ errorText: error }),
    };
    page.emit('request', result);
    return result;
}

const font = request();
assert.strictEqual(ignore(font), false, 'Current-document abort must remain fatal');
request(true);
assert.strictEqual(ignore(font), true, 'Old-document font cancellation is expected');
const notice = request();
const otherFrame = { isDetached: () => false };
request(true, undefined, otherFrame);
assert.strictEqual(ignore(notice), false, 'Another frame navigation must not mask errors');
request(true);
assert.strictEqual(ignore(notice), true, 'Old-document AJAX cancellation is expected');
const networkFailure = request(false, 'net::ERR_CONNECTION_REFUSED');
request(true);
assert.strictEqual(ignore(networkFailure), false, 'Network failures must remain fatal');
const current = request();
detached = true;
assert.strictEqual(ignore(current), true);
detached = false;
closed = true;
assert.strictEqual(ignore(current), true);
console.log('Document cancellation checks passed');
