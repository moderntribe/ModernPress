/**
 * Shared selectors for the Faceted Directory / Filter Bar front-end.
 *
 * Keep markup hooks (data-js, BEM classes, layout attrs) here so a rename
 * only needs one edit.
 */

export const CLASSES = {
	directory: 'b-faceted-directory',
	filterBar: 'b-filter-bar',
	filterGrid: 'b-filter-bar__grid',
	mobileTrigger: 'b-filter-bar__mobile-trigger',
	pagination: 'b-directory-grid__pagination',
	facet: 'tribe-facet',
	facetSearch: 'tribe-facet--search',
	facetReset: 'tribe-facet--reset',
	fancy: 'tribe-facet__fancy',
	fancyTrigger: 'tribe-facet__fancy-trigger',
	fancyTriggerLabel: 'tribe-facet__fancy-trigger-label',
	fancyList: 'tribe-facet__fancy-list',
	fancyOption: 'tribe-facet__fancy-option',
	fancyFooter: 'tribe-facet__fancy-footer',
};

export const SELECTORS = {
	directory: `.${ CLASSES.directory }`,
	form: '[data-js="faceted-directory-form"]',
	directoryGrid: '[data-js="directory-grid"]',
	liveRegion: '[data-js="directory-status"]',
	paginationLink: `.${ CLASSES.pagination } a[href]`,

	filterBar: `.${ CLASSES.filterBar }`,
	filterBarSidebar: `.${ CLASSES.filterBar }[data-filter-bar-position="sidebar"]`,
	filterGrid: `.${ CLASSES.filterGrid }`,
	mobileTrigger: `.${ CLASSES.mobileTrigger }`,
	sidebarPosition: '[data-filter-bar-position="sidebar"]',
	sidebarFacets: '[data-facet-layout="sidebar"]',
	mobileFacets: '[data-facet-layout="mobile"]',

	flyout: '[data-js="filter-flyout"]',
	trigger: '[data-js="filter-open"]',
	closeBtn: '[data-js="filter-close"]',
	showResultsBtn: '[data-js="filter-show-results"]',
	clearWrap: '[data-js="filter-clear-wrap"]',
	clearAllBtn: '[data-js="filter-clear-all"]',
	resetControl: `.${ CLASSES.facetReset } button, [data-js="filter-clear-all"]`,

	facet: `.${ CLASSES.facet }`,
	facetClear: '[data-js="facet-clear"]',
	facetApply: '[data-js="facet-apply"]',

	activeFacet: 'input:checked, option:checked',
	searchInput: `.${ CLASSES.facetSearch } input[type="search"]`,
	checkboxesRadios: 'input[type="checkbox"], input[type="radio"]',
	textInputs: 'input[type="search"], input[type="text"]',
	selects: 'select',
	fancySelect: 'select[data-fancy-dropdown="true"]',
	fancy: `.${ CLASSES.fancy }`,
	fancyTrigger: `.${ CLASSES.fancyTrigger }`,
	fancyTriggerLabel: `.${ CLASSES.fancyTriggerLabel }`,
	fancyList: `.${ CLASSES.fancyList }`,
	fancyOption: `.${ CLASSES.fancyOption }`,

	focusable:
		'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])',
};
