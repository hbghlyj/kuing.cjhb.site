(function() {
	'use strict';
	var input = document.getElementById('scform_srchtxt');
	var suggestions = document.getElementById('mobile-tag-suggestions');
	if(!input || !suggestions) return;
	var timer;
	var revision = 0;
	input.addEventListener('input', function() {
		clearTimeout(timer);
		var current = ++revision;
		var query = input.value.trim();
		suggestions.replaceChildren();
		if(Array.from(query).length < 2) return;
		timer = setTimeout(async function() {
			try {
				var response = await fetch('misc.php?mod=tag&op=search&inajax=1&searchkey=' + encodeURIComponent(query));
				if(!response.ok) return;
				var tags = await response.json();
				if(current !== revision || !Array.isArray(tags)) return;
				suggestions.replaceChildren();
				tags.forEach(function(tag) {
					if(typeof tag !== 'string') return;
					var option = document.createElement('option');
					option.value = tag;
					suggestions.appendChild(option);
				});
			} catch(error) {
				// Keep ordinary exact-name submission available on request failure.
			}
		}, 300);
	});
})();
