(function() {
	'use strict';

	if(!window.Intl || !Intl.DateTimeFormat) {
		return;
	}

	var formatter = new Intl.DateTimeFormat(undefined, {
		year: 'numeric', month: 'numeric', day: 'numeric',
		hour: 'numeric', minute: '2-digit'
	});

	function formatLocalTimestamps(root, refresh) {
		(root || document).querySelectorAll('[data-timestamp], [data-local-timestamp]').forEach(function(element) {
			if(!refresh && element.hasAttribute('data-localized-timestamp')) {
				return;
			}
			var timestamp = Number(element.getAttribute('data-local-timestamp') || element.getAttribute('data-timestamp'));
			if(isFinite(timestamp)) {
				element.textContent = formatter.format(new Date(timestamp * 1000));
				element.setAttribute('data-localized-timestamp', '1');
			}
		});
	}

	formatLocalTimestamps();
	if(window.MutationObserver && document.body) {
		new MutationObserver(function() {
			formatLocalTimestamps();
		}).observe(document.body, {childList: true, subtree: true});
	}
})();
