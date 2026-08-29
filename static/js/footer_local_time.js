(function() {
	'use strict';

	if(!window.Intl || !Intl.RelativeTimeFormat) {
		return;
	}

	var formatter = new Intl.RelativeTimeFormat(undefined, {numeric: 'auto'});

	function formatRelativeTime(timestamp) {
		var delta = timestamp - Math.floor(Date.now() / 1000);
		var absolute = Math.abs(delta);
		if(absolute < 60) {
			return formatter.format(Math.round(delta), 'second');
		}
		if(absolute < 3600) {
			return formatter.format(Math.round(delta / 60), 'minute');
		}
		if(absolute < 86400) {
			return formatter.format(Math.round(delta / 3600), 'hour');
		}
		if(absolute < 604800) {
			return formatter.format(Math.round(delta / 86400), 'day');
		}
		if(absolute < 2592000) {
			return formatter.format(Math.round(delta / 604800), 'week');
		}
		if(absolute < 31536000) {
			return formatter.format(Math.round(delta / 2592000), 'month');
		}
		return formatter.format(Math.round(delta / 31536000), 'year');
	}

	function formatLocalTimestamps(root, refresh) {
		(root || document).querySelectorAll('[data-timestamp], [data-local-timestamp]').forEach(function(element) {
			if(!refresh && element.hasAttribute('data-localized-timestamp')) {
				return;
			}
			var timestamp = Number(element.getAttribute('data-local-timestamp') || element.getAttribute('data-timestamp'));
			if(isFinite(timestamp)) {
				element.textContent = formatRelativeTime(timestamp);
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
