// Infinite scroll for the per-forum recent-topics feed on the forum index card grid.
document.querySelectorAll('.fl_topic_feed').forEach(function (feed) {
	const list = feed.querySelector('.fl_topic_list');
	if (!list) {
		return;
	}
	const sentinel = document.createElement('li');
	sentinel.className = 'fl_topic_sentinel';
	list.appendChild(sentinel);

	let loading = false;
	let done = false;

	const observer = new IntersectionObserver(function (entries) {
		entries.forEach(function (entry) {
			if (entry.isIntersecting && !loading && !done) {
				loadMore();
			}
		});
	}, { root: feed, rootMargin: '50px' });
	observer.observe(sentinel);

	function loadMore() {
		loading = true;
		const fid = feed.dataset.fid;
		const page = parseInt(feed.dataset.page, 10) + 1;
		const x = new Ajax('JSON');
		x.getJSON('forum.php?mod=misc&action=forumnewthreads&fid=' + fid + '&page=' + page, function (s) {
			loading = false;
			if (!s || !s.data) {
				return;
			}
			const items = s.data.list || [];
			items.forEach(function (item) {
				const li = document.createElement('li');
				li.innerHTML = '<a href="forum.php?mod=viewthread&tid=' + item.tid + '" class="xi2">' + item.subject + '</a> <cite>' + item.author + ' &middot; ' + item.replies + '</cite>';
				list.insertBefore(li, sentinel);
			});
			feed.dataset.page = page;
			if (!s.data.hasmore) {
				done = true;
				observer.unobserve(sentinel);
				sentinel.remove();
			}
		});
	}
});
