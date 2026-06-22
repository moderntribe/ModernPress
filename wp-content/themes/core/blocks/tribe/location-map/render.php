<?php declare(strict_types=1);

use Tribe\Plugin\Components\Blocks\Location_Map_Block_Controller;

/**
 * @var array $attributes
 */

$c = Location_Map_Block_Controller::factory( [
	'attributes'    => $attributes,
	'block_classes' => 'b-location-map',
] );

if ( $c->should_bail_early() ) {
	return;
}

$search_input_id = wp_unique_id( 'location-map-search-' );
?>
<section
	<?php
	echo get_block_wrapper_attributes( [
		'class'              => esc_attr( $c->get_block_classes() ),
		'style'              => esc_attr( trim( $c->get_block_styles() . ' ' . $c->get_map_height_style() ) ),
		'data-map-settings'  => esc_attr( $c->get_map_settings_json() ),
		'data-map-locations' => esc_attr( $c->should_render_initial_locations() ? $c->get_initial_locations_json() : '[]' ),
	] );
	?>
>
	<div class="b-location-map__grid">
		<?php if ( $c->should_show_sidebar() ) : ?>
			<div class="b-location-map__sidebar">
				<?php if ( $c->should_show_search() ) : ?>
					<div class="b-location-map__search">
						<form class="b-location-map__search-form" data-js="location-map-form">
							<label class="screen-reader-text" for="<?php echo esc_attr( $search_input_id ); ?>">
								<?php esc_html_e( 'Search locations', 'tribe' ); ?>
							</label>
							<input
								type="text"
								id="<?php echo esc_attr( $search_input_id ); ?>"
								class="b-location-map__search-input t-body"
								name="location-search"
								data-js="location-map-search"
								placeholder="<?php echo esc_attr__( 'Search by address', 'tribe' ); ?>"
							/>
							<button
								type="button"
								class="b-location-map__search-location"
								data-js="location-map-use-location"
							>
								<span class="icon"></span>
								<span class="screen-reader-text"><?php esc_html_e( 'Use my location', 'tribe' ); ?></span>
							</button>
							<button
								type="submit"
								class="b-location-map__search-submit"
								data-js="location-map-search-submit"
							>
								<span class="icon"></span>
								<span class="screen-reader-text"><?php esc_html_e( 'Search locations', 'tribe' ); ?></span>
							</button>
						</form>
					</div>
				<?php endif; ?>

				<?php if ( $c->should_show_location_list() ) : ?>
					<div class="b-location-map__locations" data-js="location-map-locations">
						<div class="b-location-map__locations-mobile-wrap">
							<button
								type="button"
								class="b-location-map__locations-mobile-toggle"
								data-js="location-map-mobile-toggle"
								data-on-text="<?php echo esc_attr__( 'Hide list', 'tribe' ); ?>"
								data-off-text="<?php echo esc_attr__( 'Show list', 'tribe' ); ?>"
							>
								<span class="b-location-map__locations-mobile-toggle-text t-body">
									<?php esc_html_e( 'Show list', 'tribe' ); ?>
								</span>
							</button>
						</div>
						<div class="b-location-map__locations-wrap">
							<p class="b-location-map__results t-body" data-js="location-map-results" hidden></p>
							<p class="b-location-map__no-results t-body" data-js="location-map-no-results" hidden></p>
							<p class="b-location-map__error t-body" data-js="location-map-error" hidden></p>
							<ul class="b-location-map__list" data-js="location-map-list"></ul>
						</div>
					</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<div class="b-location-map__map-column">
			<div class="b-location-map__map" data-js="location-map-canvas" role="region" aria-label="<?php esc_attr_e( 'Location map', 'tribe' ); ?>"></div>
		</div>
	</div>

	<div class="b-location-map__loading-overlay" data-js="location-map-loading" aria-hidden="true" hidden>
		<span class="b-location-map__loading-spinner" aria-hidden="true"></span>
	</div>
</section>
