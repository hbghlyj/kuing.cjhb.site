const { liteClient: algoliasearch } = window['algoliasearch/lite'];
window.addEventListener('load', function () {
	var search = instantsearch({
		indexName: 'kuing',
		searchClient: algoliasearch('KZZUGXICHQ', 'cfaa3668ecea0bce830d62fc30f4d0dd'),
		routing: {
			router: instantsearch.routers.history({ writeDelay: 1000 }),
			stateMapping: {
				stateToRoute(indexUiState) {
					return {
						s: indexUiState['kuing'].query,
						page: indexUiState['kuing'].page
					}
				},
				routeToState(routeState) {
					const indexUiState = {};
					indexUiState['kuing'] = {
						query: routeState.s,
						page: routeState.page
					};
					return indexUiState;
				}
			}
		}
	});

	search.addWidgets([
		/* Search box widget */
		instantsearch.widgets.searchBox({
			container: '#algolia-search-box',
			placeholder: 'Search for...',
			showReset: false,
			showSubmit: false,
			showLoadingIndicator: false,
		}),

		instantsearch.widgets.configure({
			hitsPerPage: 15,
		}),

		/* Hits widget */
		instantsearch.widgets.hits({
			container: '#algolia-hits',
			templates: {
				item(data) {
					return `<article>
<div class="ais-hits--content">
	<h2 itemprop="name headline"><a href="/forum.php?mod=redirect&goto=findpost&pid=${data.objectID}" class="ais-hits--title-link" itemprop="url">${data._highlightResult.title.value}</a><span style="color: #666;font-weight:normal;"> - ${data.totalposts - 1} 个回复 - ${data.author}</span></h2>
	<div class="excerpt">
		<p><span class="suggestion-post-content ais-hits--content-snippet">${data._snippetResult['content'].value}</span></p>
	</div>
</div>
</article>`;
				}
			}
		}),

		/* Pagination widget */
		instantsearch.widgets.pagination({
			container: '#algolia-pagination'
		}),

		/* Keywords refinement widget */
		instantsearch.widgets.refinementList({
			container: '#facet-keywords',
			attribute: 'keywords',
			operator: 'and',
			searchable: true,
			showMore: true,
			showMoreLimit: 100
		}),

		/* Author refinement widget */
		instantsearch.widgets.refinementList({
			container: '#facet-author',
			attribute: 'author',
			searchable: true,
			showMore: true,
			showMoreLimit: 100
		}),

		/* Forum refinement widget */
		instantsearch.widgets.refinementList({
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
		}),

		/* Total posts range input widget */
		instantsearch.widgets.rangeInput({
			container: '#facet-totalposts',
			attribute: 'totalposts',
			min: 0,
			max: 10000
		}),

		/* Clear all filters button */
		instantsearch.widgets.clearRefinements({
			container: '#ais-clear-refinements',
			cssClasses: {
				root: 'ais-clear-refinements'
			}
		})
	]);

	document.querySelector("#algolia-search-box").addEventListener('click', function () {
		document.getElementById('ais-facets').style.display = '';
		search.start();
		document.querySelector("#algolia-search-box input[type='search']").select()
	}, {once: true});
});