import { ready } from 'utils/events.js';

const openFlyout = ( state ) => {
	const { trigger, flyout } = state;
	if ( flyout.open || trigger.getAttribute( 'aria-expanded' ) === 'true' ) {
		return;
	}

	state.previousOverflow = document.body.style.overflow;
	flyout.showModal();
	trigger.setAttribute( 'aria-expanded', 'true' );
	document.body.style.overflow = 'hidden';
};

const handleFlyoutClose = ( state ) => {
	const { block, trigger } = state;
	trigger.setAttribute( 'aria-expanded', 'false' );
	document.body.style.overflow = state.previousOverflow;

	if ( window.getComputedStyle( trigger ).display === 'none' ) {
		block
			.querySelector( '.b-inline-navigation__desktop a[href]' )
			?.focus( { preventScroll: true } );
	} else {
		trigger.focus( { preventScroll: true } );
	}
};

const initFlyout = ( block ) => {
	const trigger = block.querySelector(
		'[data-js="inline-navigation-toggle"]'
	);
	const flyout = block.querySelector(
		'[data-js="inline-navigation-flyout"]'
	);
	const closeButton = block.querySelector(
		'[data-js="inline-navigation-close"]'
	);

	if ( ! trigger || ! flyout || ! closeButton ) {
		return;
	}

	const state = { block, trigger, flyout };
	trigger.addEventListener( 'click', () => openFlyout( state ) );
	closeButton.addEventListener( 'click', () => flyout.close() );
	flyout.addEventListener( 'close', () => handleFlyoutClose( state ) );
	flyout.addEventListener( 'click', ( event ) => {
		if ( event.target.closest( '.b-inline-navigation__list-link' ) ) {
			flyout.close();
		}
	} );

	new window.ResizeObserver( () => {
		if (
			flyout.open &&
			window.getComputedStyle( trigger ).display === 'none'
		) {
			flyout.close();
		}
	} ).observe( block );
	block.classList.add( 'is-ready' );
};

const init = () => {
	document.querySelectorAll( '.b-inline-navigation' ).forEach( initFlyout );
};

ready( init );
