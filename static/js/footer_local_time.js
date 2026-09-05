(function() {
	'use strict';

	if(!window.Intl || !Intl.DateTimeFormat || !Intl.RelativeTimeFormat) {
		return;
	}

	var dateFormatter = new Intl.DateTimeFormat(undefined, {
		year: 'numeric', month: 'numeric', day: 'numeric',
		hour: 'numeric', minute: '2-digit'
	});
	var relativeFormatter = new Intl.RelativeTimeFormat(undefined, {numeric: 'auto'});

	function formatRelativeTime(timestamp) {
		var delta = timestamp - Math.floor(Date.now() / 1000);
		var absolute = Math.abs(delta);
		if(absolute < 60) {
			return relativeFormatter.format(Math.round(delta), 'second');
		}
		if(absolute < 3600) {
			return relativeFormatter.format(Math.round(delta / 60), 'minute');
		}
		if(absolute < 86400) {
			return relativeFormatter.format(Math.round(delta / 3600), 'hour');
		}
		if(absolute < 604800) {
			return relativeFormatter.format(Math.round(delta / 86400), 'day');
		}
		if(absolute < 2592000) {
			return relativeFormatter.format(Math.round(delta / 604800), 'week');
		}
		if(absolute < 31536000) {
			return relativeFormatter.format(Math.round(delta / 2592000), 'month');
		}
		return relativeFormatter.format(Math.round(delta / 31536000), 'year');
	}

	function formatLocalTimestamps(root, refresh) {
		(root || document).querySelectorAll('[data-timestamp], [data-local-timestamp]').forEach(function(element) {
			if(!refresh && element.hasAttribute('data-localized-timestamp')) {
				return;
			}
			var timestamp = Number(element.getAttribute('data-local-timestamp') || element.getAttribute('data-timestamp'));
			if(isFinite(timestamp)) {
				element.textContent = element.id === 'footer_time_now'
					? dateFormatter.format(new Date(timestamp * 1000))
					: formatRelativeTime(timestamp);
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
