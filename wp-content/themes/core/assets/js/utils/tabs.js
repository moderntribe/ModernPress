import delegate from 'delegate';

const WP_STACKED_BREAKPOINT = 781;
const el = {
	tabBlocks: null,
};
const config = {
	keyEvents: [ 'ArrowLeft', 'ArrowRight' ],
	contentMergesOnMobile: false,
	isMobile: false,
};

/**
 * @function handleResize
 *
 * @description handle window resize event; conditionally move tab content
 */
const handleResize = () => {
	el.tabBlocks.forEach( ( block ) => {
		config.isMobile = window.innerWidth <= WP_STACKED_BREAKPOINT;

		maybeMoveTabContent( block );
	} );
};

/**
 * @function maybeMoveTabContent
 *
 * @description conditionally (based on width) copy tab content
 *
 * @param {*} block
 */
const maybeMoveTabContent = ( block ) => {
	const tabEls = block.querySelectorAll( '[role="tab"]' );

	if ( ! config.isMobile ) {
		/* if tab content exists within tab content and we've moved to a desktop size,
		   move tab content to tab content container */
		tabEls.forEach( ( tabEl ) => {
			const tabContent = tabEl.getAttribute( 'aria-controls' );
			const tabContentEl = tabEl.querySelector( `#${ tabContent }` );

			// only try to copy if we find the tab content within the tab itself
			if ( tabContentEl ) {
				const tabContentCopy = tabContentEl.cloneNode( true );
				const tabContentContainer = block.querySelector(
					'.b-vertical-tabs__tab-content'
				);
				tabContentContainer.appendChild( tabContentCopy );
				tabContentEl.remove();
			}
		} );
	} else {
		/* if tab content exists within the tab content container and we've
		   moved to a mobile size, move tab content to tab itself */
		tabEls.forEach( ( tabEl ) => {
			const tabContent = tabEl.getAttribute( 'aria-controls' );
			const tabContentEl = tabEl
				.closest( '[data-js="tabs-block"]' )
				.querySelector(
					`.b-vertical-tabs__tab-content #${ tabContent }`
				);

			// only try to copy if we find the tab content within the tab content container
			if ( tabContentEl ) {
				const tabContentCopy = tabContentEl.cloneNode( true );
				tabEl.appendChild( tabContentCopy );
				tabContentEl.remove();
			}
		} );
	}
};

/**
 * @function initializeTabBlocks
 *
 * @description handle initialization of each tab block
 */
const initializeTabBlocks = () => {
	el.tabBlocks.forEach( ( tabBlock ) => {
		// ensure the first tab panel in each block is visible on load
		const firstTabPanel = tabBlock.querySelector(
			'[role="tabpanel"]:first-child'
		);

		firstTabPanel.removeAttribute( 'hidden' );

		// conditionally (based on width) copy tab content into tabs for mobile view
		if (
			config.contentMergesOnMobile &&
			window.innerWidth <= WP_STACKED_BREAKPOINT
		) {
			config.isMobile = true;
			maybeMoveTabContent( tabBlock );
		}
	} );
};

/**
 * @function handleTabListKeyDown
 *
 * @description handle keyboard events within the tablist
 *
 * @param {*} e
 */
const handleTabListKeyDown = ( e ) => {
	if ( ! config.keyEvents.includes( e.key ) ) {
		return;
	}

	e.preventDefault();

	// get element indices to determine where to go next
	const container = e.delegateTarget.closest( '[data-js="tabs-block"]' );
	const tabButtons = [ ...container.querySelectorAll( '[role="tab"]' ) ];
	const currentIndex = tabButtons.indexOf(
		container.ownerDocument.activeElement
	);
	const lastIndex = tabButtons.length - 1;

	// handle "next" key (should always be the last item in config.keyEvents)
	if ( e.key === config.keyEvents[ config.keyEvents.length - 1 ] ) {
		// If the current trigger is the last, then cycle to the first. Otherwise, go to the next.
		const nextIndex = currentIndex + 1 > lastIndex ? 0 : currentIndex + 1;
		switchTabs( tabButtons[ nextIndex ] );
		tabButtons[ nextIndex ].focus();
	}

	// handle "previous" key (should always be the first item in config.keyEvents)
	if ( e.key === config.keyEvents[ 0 ] ) {
		// If the current trigger is the first, then cycle to the last. Otherwise, go to the previous.
		const prevIndex = currentIndex - 1 < 0 ? lastIndex : currentIndex - 1;
		switchTabs( tabButtons[ prevIndex ] );
		tabButtons[ prevIndex ].focus();
	}
};

/**
 * @function hideTabPanel
 *
 * @description actions required to hide a particular tab
 *
 * @param {HTMLElement} tab
 * @param {HTMLElement} tabButton
 */
const hideTabPanel = ( tab, tabButton ) => {
	tab.setAttribute( 'hidden', '' );
	tabButton.setAttribute( 'aria-selected', 'false' );
	tabButton.setAttribute( 'tabindex', '-1' );
};

/**
 * @function showTabPanel
 *
 * @description actions required to show a particular tab
 *
 * @param {HTMLElement} tab
 * @param {HTMLElement} tabButton
 */
const showTabPanel = ( tab, tabButton ) => {
	tab.removeAttribute( 'hidden' );
	tabButton.setAttribute( 'aria-selected', 'true' );
	tabButton.setAttribute( 'tabindex', '0' );
};

/**
 * @function switchTabs
 *
 * @description switch to the target tab and hide all others
 *
 * @param {HTMLElement} targetTabButton
 */
const switchTabs = ( targetTabButton ) => {
	// check if aria-controls is set
	const selectedTabId = targetTabButton.getAttribute( 'aria-controls' );

	if ( ! selectedTabId ) {
		return;
	}

	// grab all tab panels
	const container = targetTabButton.closest( '[data-js="tabs-block"]' );
	const tabs = container.querySelectorAll( '[role="tabpanel"]' );

	tabs.forEach( ( tab ) => {
		// grab tab button via the aria-labelledby attribute
		const tabButton = container.querySelector(
			`#${ tab.getAttribute( 'aria-labelledby' ) }`
		);

		// determine if we should hide or show this specific panel
		( tab.id === selectedTabId ? showTabPanel : hideTabPanel )(
			tab,
			tabButton
		);
	} );

	// On mobile, scroll the selected tab into view so the user doesn't have to scroll back up
	if ( config.isMobile && config.contentMergesOnMobile ) {
		const prefersReducedMotion = window.matchMedia(
			'(prefers-reduced-motion: reduce)'
		).matches;
		targetTabButton.scrollIntoView( {
			behavior: prefersReducedMotion ? 'auto' : 'smooth',
			block: 'start',
		} );
	}
};

/**
 * @function handleTabClick
 *
 * @description handle click events on a tab
 *
 * @param {*} e
 */
const handleTabClick = ( e ) =>
	e.delegateTarget.getAttribute( 'aria-selected' ) === 'false'
		? switchTabs( e.delegateTarget )
		: false;

/**
 * @function bindEvents
 *
 * @description bind events to elements within the blocks
 */
const bindEvents = () => {
	delegate( el.tabBlocks, '[role="tab"]', 'click', handleTabClick );

	delegate(
		el.tabBlocks,
		'[role="tablist"]',
		'keydown',
		handleTabListKeyDown
	);

	// handle resize event
	if ( config.contentMergesOnMobile ) {
		document.addEventListener(
			'modern_tribe/resize_executed',
			handleResize
		);
	}
};

/**
 * @function init
 *
 * @description initialize the tabs module
 *
 * @param {HTMLElement[]} blocks
 * @param {Object|false}  additionalConfig
 */
const init = ( blocks, additionalConfig = false ) => {
	el.tabBlocks = blocks;

	if ( ! el.tabBlocks ) {
		return;
	}

	if ( additionalConfig ) {
		Object.assign( config, additionalConfig );
	}

	bindEvents();
	initializeTabBlocks();
};

export default init;
