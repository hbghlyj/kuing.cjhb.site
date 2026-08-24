(function() {
	'use strict';

	var clock = document.getElementById('footer_time_now');
	if(!clock || !window.Intl || !Intl.DateTimeFormat) {
		return;
	}

	var timestamp = Number(clock.getAttribute('data-timestamp'));
	if(!isFinite(timestamp)) {
		return;
	}

	var date = new Date(timestamp * 1000);
	var formatter = new Intl.DateTimeFormat(undefined, {
		year: 'numeric',
		month: 'numeric',
		day: 'numeric',
		hour: 'numeric',
		minute: '2-digit'
	});

	clock.textContent = formatter.format(date);
})();
