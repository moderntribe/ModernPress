/**
 * Accessible multi-select dropdown over a native <select multiple>.
 */

import { SELECTORS } from './selectors';

/**
 * @param {string[]}    selected Chosen option labels.
 * @param {HTMLElement} trigger  Dropdown trigger, holding the label formats.
 * @return {string} Trigger text.
 */
const getTriggerLabel = ( selected, trigger ) => {
	if ( selected.length === 0 ) {
		return trigger.dataset.placeholder;
	}

	if ( selected.length === 1 ) {
		return selected[ 0 ];
	}

	return trigger.dataset.selectedTemplate.replace(
		'%s',
		String( selected.length )
	);
};

/**
 * Apply the option-search box's current text to the option list.
 *
 * Text-only, over options already in the DOM — terms are never re-fetched.
 *
 * ponytail: a flat match. A parent that doesn't match is hidden even when a
 * child does, so matched children keep their indent without visible context.
 * Upgrade path is unhiding ancestors of each match.
 *
 * @param {Element} wrap Dropdown wrapper.
 */
const filterOptions = ( wrap ) => {
	const search = wrap.querySelector( SELECTORS.optionSearch );

	if ( ! search ) {
		return;
	}

	const query = search.value.trim().toLowerCase();
	let matches = 0;

	wrap.querySelectorAll( SELECTORS.dropdownOption ).forEach( ( button ) => {
		const item = button.closest( SELECTORS.facetItem );
		const isMatch = button.textContent.toLowerCase().includes( query );

		if ( item ) {
			item.hidden = ! isMatch;
		}

		if ( isMatch ) {
			matches++;
		}
	} );

	const empty = wrap.querySelector( SELECTORS.dropdownEmpty );

	if ( empty ) {
		// Swap the text rather than the hidden attribute: the region has to
		// already be in the tree for a screen reader to announce the change.
		empty.textContent = matches > 0 ? '' : empty.dataset.emptyText;
	}
};

/**
 * Options the option-search has left on screen, in DOM order.
 *
 * @param {Element} wrap Dropdown wrapper.
 * @return {HTMLElement[]} Focusable options.
 */
const visibleOptions = ( wrap ) =>
	[ ...wrap.querySelectorAll( SELECTORS.dropdownOption ) ].filter(
		( button ) => ! button.closest( SELECTORS.facetItem )?.hidden
	);

/**
 * Move focus between visible options, wrapping at both ends.
 *
 * ponytail: options stay real tab stops rather than a roving tabindex, so
 * arrow keys add navigation without taking Tab away. Upgrade path is the full
 * combobox pattern with aria-activedescendant.
 *
 * @param {Element}      wrap  Dropdown wrapper.
 * @param {Element|null} from  Currently focused option, if any.
 * @param {number}       delta 1 to move down, -1 to move up.
 */
const moveFocus = ( wrap, from, delta ) => {
	const options = visibleOptions( wrap );

	if ( options.length === 0 ) {
		return;
	}

	const current = options.indexOf( from );

	if ( current === -1 ) {
		// Arriving from the search box or the trigger: enter at the near end.
		options[ delta > 0 ? 0 : options.length - 1 ].focus();
		return;
	}

	options[ ( current + delta + options.length ) % options.length ].focus();
};

const syncWrap = ( wrap ) => {
	const select = wrap.querySelector( SELECTORS.selects );
	const trigger = wrap.querySelector( SELECTORS.dropdownTrigger );

	if ( ! select || ! trigger ) {
		return;
	}

	wrap.querySelectorAll( SELECTORS.dropdownOption ).forEach( ( button ) => {
		const option = [ ...select.options ].find(
			( item ) => item.value === button.dataset.value
		);

		button.setAttribute(
			'aria-selected',
			option && option.selected ? 'true' : 'false'
		);
	} );

	const selected = [ ...select.selectedOptions ].map(
		( option ) => option.text
	);
	const label =
		wrap.querySelector( SELECTORS.dropdownTriggerLabel ) ?? trigger;

	label.textContent = getTriggerLabel( selected, trigger );

	// A clear-all wipes the search box too, so re-run the filter to match.
	filterOptions( wrap );
};

/**
 * Re-read select state and update the custom UI. Used after programmatic
 * changes such as "Clear all".
 *
 * @param {Element|Document} root Container to sync within.
 */
export const syncDropdowns = ( root = document ) => {
	root.querySelectorAll( SELECTORS.dropdown ).forEach( syncWrap );
};

const enhanceSelect = ( select ) => {
	if ( select.dataset.dropdownReady === 'true' ) {
		return;
	}

	const wrap = select.closest( SELECTORS.dropdown );
	const trigger = wrap?.querySelector( SELECTORS.dropdownTrigger );
	const popup = wrap?.querySelector( SELECTORS.dropdownPopup );

	if ( ! wrap || ! trigger || ! popup ) {
		return;
	}

	select.dataset.dropdownReady = 'true';

	wrap.querySelectorAll( SELECTORS.dropdownOption ).forEach( ( button ) => {
		button.addEventListener( 'click', () => {
			const option = [ ...select.options ].find(
				( item ) => item.value === button.dataset.value
			);

			if ( ! option ) {
				return;
			}

			option.selected = ! option.selected;
			syncWrap( wrap );
			select.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		} );
	} );

	const search = wrap.querySelector( SELECTORS.optionSearch );

	search?.addEventListener( 'input', () => filterOptions( wrap ) );

	const close = () => {
		popup.hidden = true;
		trigger.setAttribute( 'aria-expanded', 'false' );
	};

	// Selections already filter on change, so this only dismisses the popup.
	const apply = wrap.querySelector( SELECTORS.facetApply );

	if ( apply ) {
		apply.addEventListener( 'click', () => {
			close();
			trigger.focus();
		} );
	}

	trigger.addEventListener( 'click', () => {
		if ( popup.hidden ) {
			popup.hidden = false;
			trigger.setAttribute( 'aria-expanded', 'true' );
			search?.focus();
			return;
		}

		close();
	} );

	// Bound on the wrapper so Escape also works from the trigger, which sits
	// outside the popup and keeps focus when there is no search box to take it.
	wrap.addEventListener( 'keydown', ( event ) => {
		if ( popup.hidden ) {
			return;
		}

		if ( event.key === 'Escape' ) {
			event.preventDefault();
			close();
			trigger.focus();
			return;
		}

		const option = event.target.closest( SELECTORS.dropdownOption );

		if ( event.key === 'ArrowDown' || event.key === 'ArrowUp' ) {
			event.preventDefault();
			moveFocus( wrap, option, event.key === 'ArrowDown' ? 1 : -1 );
			return;
		}

		// Home/End belong to the caret while typing in the search box.
		if ( ! option || ( event.key !== 'Home' && event.key !== 'End' ) ) {
			return;
		}

		event.preventDefault();

		const options = visibleOptions( wrap );

		options[ event.key === 'Home' ? 0 : options.length - 1 ]?.focus();
	} );

	document.addEventListener( 'click', ( event ) => {
		if ( ! wrap.contains( event.target ) ) {
			close();
		}
	} );

	syncWrap( wrap );
};

export const initDropdowns = ( root = document ) => {
	root.querySelectorAll( SELECTORS.dropdownSelect ).forEach( enhanceSelect );
};
