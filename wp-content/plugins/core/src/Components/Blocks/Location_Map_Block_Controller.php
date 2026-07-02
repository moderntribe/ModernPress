<?php declare(strict_types=1);

namespace Tribe\Plugin\Components\Blocks;

use Tribe\Plugin\Components\Abstracts\Abstract_Block_Controller;
use Tribe\Plugin\Locations\Google_Maps_Config;
use Tribe\Plugin\Locations\Location_Data;
use Tribe\Plugin\Post_Types\Location\Location;
use Tribe\Plugin\Settings\Tribe_Settings;

class Location_Map_Block_Controller extends Abstract_Block_Controller {

	private const string SOURCE_MANUAL   = 'manual';
	private const string SOURCE_ALL      = 'all';
	private const string SOURCE_ENDPOINT = 'endpoint';
	private const string HEIGHT_FIXED    = 'fixed';
	private const string HEIGHT_VIEWPORT = 'viewport';
	private const string MAP_POSITION_LEFT  = 'left';
	private const string MAP_POSITION_RIGHT = 'right';

	/**
	 * @var array<int, array{id: int, value?: string, pickerLabel?: string}>
	 */
	private array $chosen_locations;
	private string $location_source;
	private string $endpoint_url;
	private bool $show_sidebar;
	private bool $show_location_cards;
	private string $map_position;
	private bool $show_search;
	private bool $show_location_list;
	private int $search_radius;
	private float $default_lat;
	private float $default_lng;
	private int $default_zoom;
	private bool $fit_bounds;
	private bool $cluster_markers;
	private string $map_height_mode;
	private int $map_height;
	private Tribe_Settings $settings;
	private Location_Data $location_data;
	private Google_Maps_Config $maps_config;

	public function __construct(
		Tribe_Settings $settings,
		Location_Data $location_data,
		Google_Maps_Config $maps_config,
		array $args = [],
	) {
		parent::__construct( $args );

		$this->settings      = $settings;
		$this->location_data = $location_data;
		$this->maps_config   = $maps_config;

		$this->location_source    = $this->attributes['locationSource'] ?? self::SOURCE_MANUAL;
		$this->chosen_locations   = $this->attributes['chosenLocations'] ?? [];
		$this->endpoint_url       = $this->attributes['endpointUrl'] ?? '';
		$this->show_sidebar          = (bool) ( $this->attributes['showSidebar'] ?? true );
		$this->show_location_cards   = (bool) ( $this->attributes['showLocationCards'] ?? false );
		$this->map_position          = $this->get_map_position();
		$this->show_search           = (bool) ( $this->attributes['showSearch'] ?? true );
		$this->show_location_list = (bool) ( $this->attributes['showLocationList'] ?? true );
		$this->search_radius      = absint( $this->attributes['searchRadius'] ?? 30 );
		$this->default_lat        = (float) ( $this->attributes['defaultLat'] ?? 39.10015 );
		$this->default_lng        = (float) ( $this->attributes['defaultLng'] ?? -94.58327 );
		$this->default_zoom       = absint( $this->attributes['defaultZoom'] ?? 11 );
		$this->fit_bounds         = (bool) ( $this->attributes['fitBounds'] ?? true );
		$this->cluster_markers    = (bool) ( $this->attributes['clusterMarkers'] ?? true );
		$this->map_height_mode    = $this->get_map_height_mode();
		$this->map_height         = absint( $this->attributes['mapHeight'] ?? 600 );

		if ( self::HEIGHT_VIEWPORT === $this->map_height_mode && ! $this->show_location_cards ) {
			$this->block_classes .= ' b-location-map--viewport-height';
		}

		if ( ! $this->show_sidebar ) {
			$this->show_search        = false;
			$this->show_location_list = false;
		} else {
			$this->show_location_cards = false;
		}

		if ( $this->show_search && ! $this->show_location_list ) {
			$this->show_location_list = true;
		}

		if ( $this->show_sidebar ) {
			$this->block_classes .= ' b-location-map--has-sidebar';
		} elseif ( $this->show_location_cards ) {
			$this->block_classes .= ' b-location-map--has-cards';
			$this->block_classes .= self::MAP_POSITION_RIGHT === $this->map_position
				? ' b-location-map--map-right'
				: ' b-location-map--map-left';
		} else {
			$this->block_classes .= ' b-location-map--map-only';
		}

		if ( ! $this->show_search ) {
			return;
		}

		$this->block_classes .= ' b-location-map--has-search';
	}

	public function should_bail_early(): bool {
		if ( ! $this->settings->has_google_maps_api_key() ) {
			return true;
		}

		if ( self::SOURCE_MANUAL === $this->location_source ) {
			return empty( $this->chosen_locations );
		}

		return false;
	}

	public function should_show_sidebar(): bool {
		return $this->show_sidebar;
	}

	public function should_show_search(): bool {
		return $this->show_search;
	}

	public function should_show_autocomplete(): bool {
		return $this->show_search && $this->settings->is_google_maps_autocomplete_enabled();
	}

	public function should_show_location_list(): bool {
		return $this->show_location_list;
	}

	public function should_show_location_cards(): bool {
		return $this->show_location_cards;
	}

	public function get_map_height_style(): string {
		if ( $this->show_location_cards || self::HEIGHT_VIEWPORT === $this->map_height_mode ) {
			return '';
		}

		return sprintf( '--location-map-height:%dpx;', $this->map_height );
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_map_settings(): array {
		return [
			'locationSource'       => $this->location_source,
			'endpointUrl'          => $this->get_locations_endpoint_url(),
			'geocodeUrl'           => $this->get_rest_url( 'tribe/v1/geocode' ),
			'defaultCenter'        => [
				'lat' => $this->default_lat,
				'lng' => $this->default_lng,
			],
			'defaultZoom'          => $this->default_zoom,
			'searchRadius'         => $this->search_radius,
			'fitBounds'            => $this->fit_bounds,
			'clusterMarkers'       => $this->cluster_markers,
			'showSearch'           => $this->show_search,
			'showLocationList'     => $this->show_location_list,
			'showLocationCards'    => $this->show_location_cards,
			'mapId'                => $this->settings->get_google_maps_map_id(),
			'autocompleteEnabled'  => $this->settings->is_google_maps_autocomplete_enabled(),
			'autocompleteMinChars' => $this->settings->get_google_maps_autocomplete_min_chars(),
			'autocompleteDebounce' => $this->settings->get_google_maps_autocomplete_debounce(),
			'searchCountry'        => strtolower( $this->maps_config->get_country() ),
		];
	}

	public function get_map_settings_json(): string {
		return wp_json_encode( $this->get_map_settings() ) ?: '{}';
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function get_initial_locations(): array {
		if ( self::SOURCE_MANUAL === $this->location_source ) {
			$post_ids = array_map(
				static fn( array $location ): int => absint( $location['id'] ?? 0 ),
				$this->chosen_locations
			);

			return $this->location_data->get_locations_by_ids( $post_ids );
		}

		if ( self::SOURCE_ALL === $this->location_source ) {
			$query = new \WP_Query( [
				'post_type'      => Location::NAME,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			] );

			return $this->location_data->get_locations_by_ids( $query->posts );
		}

		return [];
	}

	public function get_initial_locations_json(): string {
		return wp_json_encode( $this->get_initial_locations() ) ?: '[]';
	}

	public function should_render_initial_locations(): bool {
		return self::SOURCE_ENDPOINT !== $this->location_source;
	}

	private function get_map_height_mode(): string {
		$mode = $this->attributes['mapHeightMode'] ?? self::HEIGHT_FIXED;

		if ( ! in_array( $mode, [ self::HEIGHT_FIXED, self::HEIGHT_VIEWPORT ], true ) ) {
			return self::HEIGHT_FIXED;
		}

		return $mode;
	}

	private function get_map_position(): string {
		$position = $this->attributes['mapPosition'] ?? self::MAP_POSITION_LEFT;

		if ( ! in_array( $position, [ self::MAP_POSITION_LEFT, self::MAP_POSITION_RIGHT ], true ) ) {
			return self::MAP_POSITION_LEFT;
		}

		return $position;
	}

	private function get_locations_endpoint_url(): string {
		if ( self::SOURCE_ENDPOINT === $this->location_source && $this->endpoint_url !== '' ) {
			return $this->endpoint_url;
		}

		return $this->get_rest_url( 'tribe/v1/locations' );
	}

	private function get_rest_url( string $route ): string {
		$url      = rest_url( $route );
		$relative = wp_make_link_relative( $url );

		return is_string( $relative ) && $relative !== '' ? $relative : $url;
	}

}
