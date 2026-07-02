import { ready } from 'utils/events';
import { A11y, Pagination } from 'swiper/modules';
import Swiper from 'swiper';

import 'swiper/css';
import 'swiper/css/a11y';
import 'swiper/css/pagination';

const SELECTOR = '.b-comparison-table [data-swiper-settings]';

/**
 * @param {HTMLElement[]} swipers Swiper root elements within comparison tables.
 */
const bindEvents = ( swipers ) => {
	swipers.forEach( ( swiper ) => {
		if ( swiper.classList.contains( 'swiper-initialized' ) ) {
			return;
		}

		const args = JSON.parse(
			swiper.getAttribute( 'data-swiper-settings' )
		);
		const pagination = swiper.querySelector( '.swiper-pagination' );

		new Swiper( swiper, {
			...args,
			modules: [ A11y, Pagination ],
			pagination: {
				el: pagination,
				clickable:
					pagination?.getAttribute( 'data-clickable' ) === 'true',
			},
		} );
	} );
};

const init = () => {
	const swipers = document.querySelectorAll( SELECTOR );

	if ( ! swipers.length ) {
		return;
	}

	bindEvents( swipers );
};

ready( init );
