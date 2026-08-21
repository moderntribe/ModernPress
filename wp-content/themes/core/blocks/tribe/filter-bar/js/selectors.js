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
	facetWrapper: 'b-filter-bar__facet',
	mobileTrigger: 'b-filter-bar__mobile-trigger',
	pagination: 'b-directory-grid__pagination',
	facet: 'tribe-facet',
	facetItem: 'tribe-facet__item',
	facetSearchInput: 'tribe-facet__search-input',
	facetReset: 'tribe-facet--reset',
	dropdown: 'tribe-facet__dropdown',
	dropdownTrigger: 'tribe-facet__dropdown-trigger',
	dropdownTriggerLabel: 'tribe-facet__dropdown-trigger-label',
	dropdownPopup: 'tribe-facet__dropdown-popup',
	dropdownList: 'tribe-facet__dropdown-list',
	dropdownOption: 'tribe-facet__dropdown-option',
	dropdownEmpty: 'tribe-facet__dropdown-empty',
};

export const SELECTORS = {
	directory: `.${ CLASSES.directory }`,
	form: '[data-js="faceted-directory-form"]',
	directoryGrid: '[data-js="directory-grid"]',
	liveRegion: '[data-js="directory-status"]',
	paginationLink: `.${ CLASSES.pagination } a[href]`,

	filterBar: `.${ CLASSES.filterBar }`,
	facetWrapper: `.${ CLASSES.facetWrapper }`,
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
	facetItem: `.${ CLASSES.facetItem }`,
	facetReset: `.${ CLASSES.facetReset }`,
	facetClear: '[data-js="facet-clear"]',
	facetApply: '[data-js="facet-apply"]',

	activeFacet: 'input:checked, option:checked',
	searchInput: `.${ CLASSES.facetSearchInput }`,
	checkboxes: 'input[type="checkbox"]',
	textInputs: 'input[type="search"], input[type="text"]',
	selects: 'select',
	dropdownSelect: 'select[data-dropdown="true"]',
	dropdown: `.${ CLASSES.dropdown }`,
	dropdownTrigger: `.${ CLASSES.dropdownTrigger }`,
	dropdownTriggerLabel: `.${ CLASSES.dropdownTriggerLabel }`,
	dropdownPopup: `.${ CLASSES.dropdownPopup }`,
	dropdownList: `.${ CLASSES.dropdownList }`,
	dropdownOption: `.${ CLASSES.dropdownOption }`,
	dropdownEmpty: `.${ CLASSES.dropdownEmpty }`,
	optionSearch: '[data-js="facet-option-search"]',

	focusable:
		'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])',
};
