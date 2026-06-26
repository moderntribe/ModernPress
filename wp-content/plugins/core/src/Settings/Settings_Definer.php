<?php declare(strict_types=1);

namespace Tribe\Plugin\Settings;

use DI;
use Tribe\Plugin\Core\Interfaces\Definer_Interface;

class Settings_Definer implements Definer_Interface {

	public const PAGES = 'libs.settings.pages';

	public function define(): array {
		return [
			// add the settings screens to the global array
			self::PAGES => DI\add( [
				DI\get( Login_Settings::class ),
				DI\get( Tribe_Settings::class ),
			] ),
		];
	}

}
