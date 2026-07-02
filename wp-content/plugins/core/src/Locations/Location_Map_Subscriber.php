<?php declare(strict_types=1);

namespace Tribe\Plugin\Locations;

use Tribe\Plugin\Core\Abstract_Subscriber;
use Tribe\Plugin\Settings\Tribe_Settings;

class Location_Map_Subscriber extends Abstract_Subscriber {

	public function register(): void {
		$this->container->get( Google_Maps::class )->register();

		add_action( 'enqueue_block_editor_assets', function (): void {
			$tribe_settings = $this->container->get( Tribe_Settings::class );

			wp_add_inline_script(
				'wp-blocks',
				'window.tribeLocationMap = ' . wp_json_encode( [
					'hasGoogleMapsApiKey'         => $tribe_settings->has_google_maps_api_key(),
					'settingsUrl'                 => admin_url(
						'options-general.php?page=' . Tribe_Settings::PAGE_SLUG
					),
					'defaultLocationsEndpointUrl' => $this->get_locations_endpoint_url(),
				] ) . ';',
				'before'
			);
		} );
	}

	private function get_locations_endpoint_url(): string {
		$url      = rest_url( 'tribe/v1/locations' );
		$relative = wp_make_link_relative( $url );

		return is_string( $relative ) && $relative !== '' ? $relative : $url;
	}

}
