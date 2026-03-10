import { ready } from 'utils/events';
import { A11y, Autoplay, Navigation, Pagination } from 'swiper/modules';
import Swiper from 'swiper';

const el = {
	swipers: null,
};

const bindEvents = () => {
	el.swipers.forEach( ( swiper ) => {
		const args = JSON.parse(
			swiper.getAttribute( 'data-swiper-settings' )
		);
		const modules = [ A11y ];
		const prevButton = swiper.querySelector( '.swiper-button-prev' );
		const nextButton = swiper.querySelector( '.swiper-button-next' );
		const pagination = swiper.querySelector( '.swiper-pagination' );

		if ( prevButton || nextButton ) {
			modules.push( Navigation );
			args.navigation = {
				nextEl: nextButton,
				prevEl: prevButton,
			};
		}

		if ( pagination ) {
			modules.push( Pagination );
			args.pagination = {
				el: pagination,
				clickable:
					pagination.getAttribute( 'data-clickable' ) === 'true',
			};
		}

		if ( args?.autoplay ) {
			modules.push( Autoplay );
		}

		console.log( 'Initializing Swiper with args:', args );

		new Swiper( swiper, {
			...args,
			modules,
		} );
	} );
};

/**
 * @function cacheElements
 * @description Caches DOM elements required for initializing Swiper instances.
 */
const cacheElements = () => {
	el.swipers = document.querySelectorAll( '[data-swiper-settings]' );
};

const init = () => {
	cacheElements();
	bindEvents();
};

ready( init );
