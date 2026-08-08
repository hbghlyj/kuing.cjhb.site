import "https://unpkg.com/instantsearch.js";
import liteClient from "https://unpkg.com/algoliasearch@4.25.3/dist/algoliasearch-lite.esm.browser.js";
export function initSearch(lang, forumlist, options = {}) {
	var search = instantsearch({
		indexName: 'kuing',
		searchClient: liteClient('KZZUGXICHQ', 'cfaa3668ecea0bce830d62fc30f4d0dd')
	});
	const searchBoxCssClasses = {
		input: options.inputClassName || 'ais-SearchBox-input'
	};
	if(options.submitClassName) {
		searchBoxCssClasses.submit = options.submitClassName;
	}

	if (document.querySelector('#facet-totalposts') && typeof instantsearch.widgets.rangeInput !== 'function') {
		console.warn('Algolia rangeInput widget unavailable for #facet-totalposts container');
	}

	search.addWidgets([
		/* Search box widget */
		document.querySelector('#algolia-search-box') ? instantsearch.widgets.searchBox({
			container: '#algolia-search-box',
			placeholder: lang['search'],
			showReset: !!options.showReset,
			showSubmit: !!options.showSubmit,
			showLoadingIndicator: false,
			cssClasses: searchBoxCssClasses,
		}) : null,

		instantsearch.widgets.configure({
			hitsPerPage: 15,
		}),

		/* Hits widget */
		document.querySelector('#algolia-hits') ? instantsearch.widgets.hits({
			container: '#algolia-hits',
			templates: {
				empty: lang['no_results'],
				item(data) {
					const query = search.helper && search.helper.state ? search.helper.state.query : '';
					const highlight = query ? '&highlight=' + encodeURIComponent(query.replace(/["'<>]|CONTENT-TRANSFER-ENCODING/gi, '')) : '';
					return `<article>
<div class="ais-hits--content">
	<h2 itemprop="name headline"><a href="/forum.php?mod=redirect&goto=findpost&pid=${data.objectID}${highlight}" target="_blank" class="ais-hits--title-link" itemprop="url">${data._highlightResult.title.value}</a> <span style="color: #666;font-weight:normal;">${data.author} (${data.totalposts - 1} ${lang['replies']}) ${data.date}</span></h2>
	<div class="excerpt">
		<p><span class="suggestion-post-content ais-hits--content-snippet">${data._snippetResult['content'].value}</span></p>
	</div>
</div>
</article>`;
				}
			}
		}) : null,

		/* Pagination widget */
		document.querySelector('#algolia-pagination') ? instantsearch.widgets.pagination({
			container: '#algolia-pagination'
		}) : null,

		/* Keywords refinement widget */
		document.querySelector('#facet-keywords') ? instantsearch.widgets.refinementList({
			container: '#facet-keywords',
			attribute: 'keywords',
			operator: 'and',
			searchable: true,
			showMore: true,
			showMoreLimit: 100,
			templates: {
				searchableNoResults: lang['no_results'],
				showMoreText({ isShowingMore
				}) {
					return isShowingMore ? lang['less'] : lang['more'];
				}
			}
		}) : null,

		/* Author refinement widget */
		document.querySelector('#facet-author') ? instantsearch.widgets.refinementList({
			container: '#facet-author',
			attribute: 'author',
			searchable: true,
			showMore: true,
			showMoreLimit: 100,
			templates: {
				searchableNoResults: lang['no_results'],
				showMoreText({ isShowingMore
				}) {
					return isShowingMore ? lang['less'] : lang['more'];
				}
			}
		}) : null,

		/* Forum refinement widget */
		document.querySelector('#facet-forum') ? instantsearch.widgets.refinementList({
			container: '#facet-forum',
			attribute: 'forum',
			templates: {
				item(data) {
					return `<label class="ais-RefinementList-label">
<input type="checkbox" value="${data.label}" ${data.isRefined ? 'checked' : ''} class="ais-refinement-list--checkbox">
<span class="ais-refinement-list--label-text">${forumlist[data.label]}</span>
<span class="ais-RefinementList-count">${data.count}</span>
</label>`;
				}
			}
		}) : null,

		/* Total posts range input widget */
		(document.querySelector('#facet-totalposts') && typeof instantsearch.widgets.rangeInput === 'function') ? instantsearch.widgets.rangeInput({
			container: '#facet-totalposts',
			attribute: 'totalposts',
			min: 0,
			max: 10000
		}) : null,

		/* Clear all filters button */
		document.querySelector('#ais-clear-refinements') ? instantsearch.widgets.clearRefinements({
			container: '#ais-clear-refinements',
			cssClasses: {
				root: 'ais-clear-refinements'
			},
			templates: {
				resetLabel: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 21"><g fill="none" fill-rule="evenodd" stroke-width="2" stroke="#000" stroke-linecap="round" stroke-linejoin="round" transform="matrix(0 1 1 0 2.5 2.5)"><path d="m3.98652376 1.07807068c-2.38377179 1.38514556-3.98652376 3.96636605-3.98652376 6.92192932 0 4.418278 3.581722 8 8 8s8-3.581722 8-8-3.581722-8-8-8"/><path d="m4 1v4h-4" transform="matrix(1 0 0 -1 0 6)"/></g></svg>'
			}
		}) : null
	].filter(Boolean));
	search._initialResults = {
		kuing: {}
	};
	const wrapper = document.getElementById('ais-wrapper');
	const facets = document.getElementById('ais-facets');
	function syncSubmitVisibility(searchInput) {
		if(!options.hideSubmitWhenHasQuery) {
			return;
		}
		const submitButton = document.querySelector('#algolia-search-box .ais-SearchBox-submit');
		if(!submitButton || !searchInput) {
			return;
		}
		submitButton.style.display = searchInput.value.trim().length > 0 ? 'none' : '';
	}
	function syncResetVisibility(searchInput) {
		if(!options.showReset) {
			return;
		}
		const resetButton = document.querySelector('#algolia-search-box .ais-SearchBox-reset');
		if(!resetButton || !searchInput) {
			return;
		}
		if(searchInput.value.trim().length > 0) {
			resetButton.removeAttribute('hidden');
			resetButton.style.display = '';
		} else {
			resetButton.setAttribute('hidden', '');
			resetButton.style.display = 'none';
		}
	}
	function syncSubmitVisibilitySoon(searchInput) {
		if(!options.hideSubmitWhenHasQuery) {
			return;
		}
		window.setTimeout(function () {
			syncSubmitVisibility(searchInput);
			syncResetVisibility(searchInput);
		}, 0);
	}
	function applySearchBoxOptions() {
		const submitButton = document.querySelector('#algolia-search-box .ais-SearchBox-submit');
		const resetButton = document.querySelector('#algolia-search-box .ais-SearchBox-reset');
		if(submitButton && options.submitAttributes) {
			Object.entries(options.submitAttributes).forEach(function ([key, value]) {
				submitButton.setAttribute(key, value);
			});
		}
		if(submitButton && options.stripSubmitIcon) {
			submitButton.textContent = '';
		}
		if(resetButton && options.stripResetIcon) {
			resetButton.textContent = '';
		}
	}
	function syncWrapperVisibility(searchInput) {
		if(!wrapper || !searchInput) {
			return;
		}
		const hasQuery = searchInput.value.trim().length > 0;
		wrapper.style.display = hasQuery ? 'flex' : 'none';
		if(facets) {
			facets.style.display = hasQuery ? '' : 'none';
		}
		syncSubmitVisibility(searchInput);
		syncResetVisibility(searchInput);
		syncSubmitVisibilitySoon(searchInput);
	}
	function bindSearchInputVisibility() {
		const searchInput = document.querySelector("#algolia-search-box input[type='search']");
		applySearchBoxOptions();
		if(!searchInput) {
			return;
		}
		if(!searchInput.dataset.algoliaVisibilityBound) {
			searchInput.addEventListener('click', function () {
				if(facets && searchInput.value.trim().length > 0) {
					facets.style.display = '';
				}
			});
			searchInput.addEventListener('input', function () {
				syncWrapperVisibility(searchInput);
			});
			searchInput.dataset.algoliaVisibilityBound = '1';
		}
		syncWrapperVisibility(searchInput);
	}
	search.start();
	search.on('render', bindSearchInputVisibility);
	bindSearchInputVisibility();
}
