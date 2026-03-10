import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';
import { DESKTOP_BREAKPOINT } from 'config/options';

export default function save( props ) {
	const blockProps = useBlockProps.save();
	const {
		attributes: {
			autoplay,
			autoplayDelay,
			centeredSlides,
			loop,
			navigation,
			pagination,
			paginationClickable,
			slidesPerGroup,
			slidesPerViewAuto,
			slidesPerView,
			spaceBetween,
			speed,
		},
	} = props;

	const settings = {};

	// handle autoplay (we take some setting liberties here)
	if ( autoplay ) {
		settings.autoplay = {
			delay: parseInt( autoplayDelay ),
			disableOnInteraction: false,
			pauseOnMouseEnter: true,
		};
	}

	if ( centeredSlides ) {
		settings.centeredSlides = true;
	}

	if ( loop ) {
		settings.loop = true;

		/**
		 * These params together are recommended to prevent issues with
		 * keyboard accessibility and slide visibility when using loop mode.
		 *
		 * @see https://swiperjs.com/swiper-api#param-watchSlidesProgress
		 * @see https://swiperjs.com/swiper-api#param-loopAdditionalSlides
		 */
		settings.watchSlidesProgress = true;
		settings.loopAdditionalSlides = 0;
	}

	if ( slidesPerGroup > 1 ) {
		settings.slidesPerGroup = parseInt( slidesPerGroup );
	}

	if ( slidesPerView > 1 ) {
		settings.slidesPerView = parseInt( slidesPerView );
	}

	// If we're using multiple slides per view or group, set breakpoints
	if ( settings?.slidesPerView || settings?.slidesPerGroup ) {
		settings.breakpoints = {
			[ DESKTOP_BREAKPOINT ]: {
				slidesPerView: settings.slidesPerView || 1,
				slidesPerGroup: settings.slidesPerGroup || 1,
			},
		};

		// Since breakpoints are "min-width", we need to set the mobile defaults here
		settings.slidesPerView = 1;
		settings.slidesPerGroup = 1;
	}

	if ( slidesPerViewAuto ) {
		settings.slidesPerView = 'auto';
	}

	if ( spaceBetween > 0 ) {
		settings.spaceBetween = parseInt( spaceBetween );
	}

	if ( speed > 0 ) {
		settings.speed = parseInt( speed );
	}

	return (
		<div { ...blockProps }>
			<div
				className="swiper"
				data-swiper-settings={ JSON.stringify( settings ) }
			>
				<div className="swiper-wrapper">
					<InnerBlocks.Content />
				</div>
				{ navigation || pagination ? (
					<div className="swiper-navigation">
						{ navigation && (
							<div className="swiper-navigation__buttons">
								<button
									type="button"
									className="swiper-button-prev"
								></button>
								<button
									type="button"
									className="swiper-button-next"
								></button>
							</div>
						) }
						{ pagination && (
							<div
								className="swiper-pagination"
								data-clickable={ paginationClickable }
							></div>
						) }
					</div>
				) : (
					''
				) }
			</div>
		</div>
	);
}
