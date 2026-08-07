/**
 * Accessible fancy dropdown over a native <select multiple>.
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

const syncWrap = ( wrap ) => {
	const select = wrap.querySelector( SELECTORS.selects );
	const trigger = wrap.querySelector( SELECTORS.fancyTrigger );

	if ( ! select || ! trigger ) {
		return;
	}

	wrap.querySelectorAll( SELECTORS.fancyOption ).forEach( ( button ) => {
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
	const label = wrap.querySelector( SELECTORS.fancyTriggerLabel ) ?? trigger;

	label.textContent = getTriggerLabel( selected, trigger );
};

/**
 * Re-read select state and update the custom UI. Used after programmatic
 * changes such as "Clear all".
 *
 * @param {Element|Document} root Container to sync within.
 */
export const syncFancyDropdowns = ( root = document ) => {
	root.querySelectorAll( SELECTORS.fancy ).forEach( syncWrap );
};

const enhanceSelect = ( select ) => {
	if ( select.dataset.fancyReady === 'true' ) {
		return;
	}

	const wrap = select.closest( SELECTORS.fancy );
	const trigger = wrap?.querySelector( SELECTORS.fancyTrigger );
	const list = wrap?.querySelector( SELECTORS.fancyList );

	if ( ! wrap || ! trigger || ! list ) {
		return;
	}

	select.dataset.fancyReady = 'true';

	wrap.querySelectorAll( SELECTORS.fancyOption ).forEach( ( button ) => {
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

	const close = () => {
		list.hidden = true;
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
		if ( list.hidden ) {
			list.hidden = false;
			trigger.setAttribute( 'aria-expanded', 'true' );
			return;
		}

		close();
	} );

	document.addEventListener( 'click', ( event ) => {
		if ( ! wrap.contains( event.target ) ) {
			close();
		}
	} );

	syncWrap( wrap );
};

export const initFancyDropdowns = ( root = document ) => {
	root.querySelectorAll( SELECTORS.fancySelect ).forEach( enhanceSelect );
};
