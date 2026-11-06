function trackDocumentRequests(page) {
    const generations = new WeakMap();
    const requests = new WeakMap();
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

module.exports = { trackDocumentRequests };
