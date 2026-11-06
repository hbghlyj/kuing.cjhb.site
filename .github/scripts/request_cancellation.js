function trackDocumentRequests(page) {
    const generations = new WeakMap();
    const requests = new WeakMap();
    page.on('framenavigated', frame => {
        generations.set(frame, (generations.get(frame) || 0) + 1);
    });
    page.on('request', request => {
        const frame = request.frame();
        if(!frame) return;
        let generation = generations.get(frame) || 0;
        if(request.isNavigationRequest()) {
            generations.set(frame, ++generation);
        }
        requests.set(request, { frame, generation });
    });
    return request => {
        if(request.failure()?.errorText !== 'net::ERR_ABORTED') return false;
        const document = requests.get(request);
        if(!document) return false;
        return page.isClosed() || document.frame.isDetached() ||
            (generations.get(document.frame) || 0) > document.generation;
    };
}

async function waitForDocumentCancellation(page, request, isDocumentCancellation) {
    if(isDocumentCancellation(request)) return true;
    if(request.failure()?.errorText !== 'net::ERR_ABORTED') return false;
    // Chromium can report cancellation before the navigation/teardown event.
    return new Promise(resolve => {
        const events = ['request', 'framenavigated', 'framedetached', 'close'];
        const finish = result => {
            clearTimeout(timer);
            for(const event of events) page.off(event, check);
            resolve(result);
        };
        const check = () => {
            if(isDocumentCancellation(request)) finish(true);
        };
        const timer = setTimeout(() => finish(isDocumentCancellation(request)), 1000);
        for(const event of events) page.on(event, check);
        check();
    });
}

module.exports = { trackDocumentRequests, waitForDocumentCancellation };
