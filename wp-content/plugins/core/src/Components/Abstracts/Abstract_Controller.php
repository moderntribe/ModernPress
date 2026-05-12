<?php declare(strict_types=1);

namespace Tribe\Plugin\Components\Abstracts;

abstract class Abstract_Controller {

	/**
	 * @param array $args
	 *
	 * @throws \DI\DependencyException
	 * @throws \DI\NotFoundException
	 *
	 * @return static
	 */
	public static function factory( array $args = [] ): Abstract_Controller {
		return tribe_project()->container()->make( static::class, [ 'args' => $args ] );
	}

}
