// import dependencies
import { createBlock } from '@wordpress/blocks';
import {
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import {
	Button,
	Flex,
	FlexItem,
	PanelBody,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

// import swiper
import { A11y, Navigation, Pagination } from 'swiper/modules';
import Swiper from 'swiper';

const TEMPLATE = [
	[ 'tribe/carousel-slide' ],
	[ 'tribe/carousel-slide' ],
	[ 'tribe/carousel-slide' ],
];

export default function Edit( props ) {
	const blockProps = useBlockProps();
	const block = useRef( null );
	const dispatch = useDispatch( 'core/block-editor' );
	const {
		clientId,
		attributes: {
			activeIndex,
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
			totalSlides,
		},
		setAttributes,
	} = props;
	const select = useSelect( 'core/block-editor' );
	const innerBlocks = select.getBlocks( clientId );
	const { children } = useInnerBlocksProps( blockProps, {
		allowedBlocks: [ 'tribe/carousel-slide' ],
		template: TEMPLATE,
		renderAppender: false,
	} );

	// check for inner block changes and update total slides to get a new render
	useEffect( () => {
		setAttributes( {
			totalSlides: select.getBlocks( clientId ).length,
		} );
	}, [ innerBlocks, clientId, select, setAttributes ] );

	/**
	 * @function slidePrev
	 *
	 * @description slides to the previous slide and updates active index
	 */
	const slidePrev = () => {
		block.current.swiper.slidePrev( speed );

		setAttributes( {
			activeIndex: activeIndex - 1,
		} );
	};

	/**
	 * @function slideNext
	 *
	 * @description slides to the next slide and updates active index
	 */
	const slideNext = () => {
		block.current.swiper.slideNext( speed );

		setAttributes( {
			activeIndex: activeIndex + 1,
		} );
	};

	/**
	 * @function addSlide
	 *
	 * @description adds a new slide block, inserts it, then updates attributes to allow us to edit the newly added slide immediately
	 */
	const addSlide = () => {
		const newSlide = createBlock( 'tribe/carousel-slide' );
		dispatch
			.insertBlock( newSlide, totalSlides, clientId, true )
			.then( () => {
				setAttributes( {
					activeIndex: totalSlides,
					totalSlides: totalSlides + 1,
				} );
			} );
	};

	// setup swiper based on attributes
	useEffect( () => {
		// destroy current swiper
		if ( block.current.swiper ) {
			block.current.swiper.destroy();
		}

		if ( block.current && ! block.current.swiper ) {
			const args = {
				modules: [ Navigation, Pagination, A11y ],
				initialSlide: activeIndex,
			};

			// we never want to run autoplay within the editor so we'll exclude it from these settings
			// we also never want to add loop to the editor as that will cause issues with the block editor

			// handle centered slides
			if ( centeredSlides ) {
				args.centeredSlides = true;
			}

			// handle navigation params
			if ( navigation ) {
				args.navigation = {
					nextEl: block.current.querySelector(
						'.swiper-button-next'
					),
					prevEl: block.current.querySelector(
						'.swiper-button-prev'
					),
				};
			}

			// handle pagination params
			if ( pagination ) {
				args.pagination = {
					el: block.current.querySelector( '.swiper-pagination' ),
					clickable: paginationClickable,
				};
			}

			// handle slides per group
			if ( slidesPerGroup > 1 ) {
				args.slidesPerGroup = slidesPerGroup;
			}

			// handle slides per view (will be overwritten if slidesPerViewAuto = true)
			if ( slidesPerView > 1 ) {
				args.slidesPerView = slidesPerView;
			}

			// handle slides per view set to "auto" (will override numbered slidesPerView)
			if ( slidesPerViewAuto ) {
				args.slidesPerView = 'auto';
			}

			// handle space between
			if ( spaceBetween > 0 ) {
				args.spaceBetween = spaceBetween;
			}

			// handle speed
			if ( speed > 0 ) {
				args.speed = speed;
			}

			new Swiper( block.current, {
				...args,
				on: {
					slideChange: ( swiper ) => {
						setAttributes( {
							activeIndex: swiper.activeIndex,
						} );
					},
				},
			} );
		}
	}, [
		centeredSlides,
		loop,
		navigation,
		pagination,
		paginationClickable,
		slidesPerGroup,
		slidesPerView,
		slidesPerViewAuto,
		spaceBetween,
		speed,
		totalSlides,
		activeIndex,
		setAttributes,
	] );

	return (
		<section { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Carousel Swiper Settings', 'tribe' ) }>
					<ToggleControl
						label={ __( 'Autoplay', 'tribe' ) }
						help={ __(
							'Automatically play the carousel',
							'tribe'
						) }
						checked={ autoplay }
						onChange={ ( value ) =>
							setAttributes( { autoplay: value } )
						}
					/>
					<TextControl
						label={ __( 'Autoplay Delay', 'tribe' ) }
						help={ __(
							'The delay between slides. Only applies when the Autoplay setting is active',
							'tribe'
						) }
						type="number"
						value={ autoplayDelay }
						onChange={ ( value ) =>
							setAttributes( {
								autoplayDelay: parseInt( value ),
							} )
						}
					/>
					<ToggleControl
						label={ __( 'Centered Slides', 'tribe' ) }
						help={ __(
							'Center the active slide in the viewport',
							'tribe'
						) }
						checked={ centeredSlides }
						onChange={ ( value ) =>
							setAttributes( { centeredSlides: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Loop', 'tribe' ) }
						help={ __(
							'Enable to allow continuous loop of slides',
							'tribe'
						) }
						checked={ loop }
						onChange={ ( value ) =>
							setAttributes( { loop: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Navigation', 'tribe' ) }
						help={ __(
							'Turn on navigation arrows for this carousel',
							'tribe'
						) }
						checked={ navigation }
						onChange={ ( value ) =>
							setAttributes( { navigation: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Pagination', 'tribe' ) }
						help={ __(
							'Turn on pagination for this carousel',
							'tribe'
						) }
						checked={ pagination }
						onChange={ ( value ) =>
							setAttributes( { pagination: value } )
						}
					/>
					{ pagination ? (
						<ToggleControl
							label={ __( 'Pagination Clickable?', 'tribe' ) }
							help={ __(
								'If pagination is active, should it be interactable?',
								'tribe'
							) }
							checked={ paginationClickable }
							onChange={ ( value ) =>
								setAttributes( { paginationClickable: value } )
							}
						/>
					) : (
						''
					) }
					<ToggleControl
						label={ __( 'Slides Per View Auto', 'tribe' ) }
						help={ __(
							'This carousel should set slides per view to "auto". This will override the value set in Slides Per View',
							'tribe'
						) }
						checked={ slidesPerViewAuto }
						onChange={ ( value ) =>
							setAttributes( { slidesPerViewAuto: value } )
						}
					/>
					{ ! slidesPerViewAuto ? (
						<TextControl
							label={ __( 'Slides Per View', 'tribe' ) }
							help={ __(
								'Number of slides per view (slides visible at the same time)',
								'tribe'
							) }
							type="number"
							value={ slidesPerView }
							onChange={ ( value ) =>
								setAttributes( {
									slidesPerView: parseInt( value ),
								} )
							}
						/>
					) : (
						''
					) }
					<TextControl
						label={ __( 'Slides Per Group', 'tribe' ) }
						help={ __(
							'Set numbers of slides to define and enable group sliding. Useful to use when Slides Per View is greater than 1',
							'tribe'
						) }
						type="number"
						value={ slidesPerGroup }
						onChange={ ( value ) =>
							setAttributes( {
								slidesPerGroup: parseInt( value ),
							} )
						}
					/>
					<TextControl
						label={ __( 'Space Between', 'tribe' ) }
						help={ __( 'Distance between slides in px', 'tribe' ) }
						type="number"
						value={ spaceBetween }
						onChange={ ( value ) =>
							setAttributes( { spaceBetween: parseInt( value ) } )
						}
					/>
					<TextControl
						label={ __( 'Speed', 'tribe' ) }
						help={ __(
							'Duration of transition between slides (in ms)',
							'tribe'
						) }
						type="number"
						value={ speed }
						onChange={ ( value ) =>
							setAttributes( { speed: parseInt( value ) } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div ref={ block } className="swiper">
				<div className="swiper-wrapper">{ children }</div>
				{ navigation || pagination ? (
					<div className="swiper-navigation">
						{ navigation && (
							<div className="swiper-navigation__buttons">
								<button
									type="button"
									className="swiper-button-prev"
									title={ __( 'Previous Slide', 'tribe' ) }
								></button>
								<button
									type="button"
									className="swiper-button-next"
									title={ __( 'Next Slide', 'tribe' ) }
								></button>
							</div>
						) }
						{ pagination && (
							<div className="swiper-pagination"></div>
						) }
					</div>
				) : (
					''
				) }
			</div>
			<Flex align="center" justify="flex-start">
				<FlexItem>
					<Button variant="primary" onClick={ addSlide }>
						{ __( 'Add Slide', 'tribe' ) }
					</Button>
				</FlexItem>
				<FlexItem>
					<Button
						variant="secondary"
						disabled={ activeIndex === 0 }
						onClick={ slidePrev }
					>
						{ __( 'Previous Slide', 'tribe' ) }
					</Button>
				</FlexItem>
				<FlexItem>
					<Button
						variant="secondary"
						disabled={ activeIndex === totalSlides - 1 }
						onClick={ slideNext }
					>
						{ __( 'Next Slide', 'tribe' ) }
					</Button>
				</FlexItem>
			</Flex>
		</section>
	);
}
