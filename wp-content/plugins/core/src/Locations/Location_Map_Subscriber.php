<?php declare(strict_types=1);

namespace Tribe\Plugin\Locations;

use Tribe\Plugin\Core\Abstract_Subscriber;
use Tribe\Plugin\Settings\Tribe_Settings;

class Location_Map_Subscriber extends Abstract_Subscriber {

	public function register(): void {
		$this->container->get( Google_Maps::class )->register();

		add_filter( 'block_editor_settings_all', function ( array $settings ): array {
			$tribe_settings = $this->container->get( Tribe_Settings::class );

			$settings['tribeLocationMap'] = [
				'hasGoogleMapsApiKey' => $tribe_settings->has_google_maps_api_key(),
				'settingsUrl'         => admin_url(
					'options-general.php?page=' . Tribe_Settings::PAGE_SLUG
				),
			];

			return $settings;
		}, 10, 1 );
	}

}
