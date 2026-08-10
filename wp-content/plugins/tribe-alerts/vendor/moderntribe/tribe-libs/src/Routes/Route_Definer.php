<?php

declare (strict_types=1);
/**
 * Defines any routes to be registered for the project.
 *
 * @package Tribe\Project\Routes
 */
namespace Tribe\Alert_Scoped\Tribe\Libs\Routes;

use Tribe\Alert_Scoped\DI;
use Tribe\Alert_Scoped\Tribe\Libs\Container\Definer_Interface;
/**
 * Class to define custom routes used in the project.
 */
class Route_Definer implements Definer_Interface
{
    /**
     * The Route DI group to use.
     *
     * @var string
     */
    public const ROUTES = 'libs.routes';
    /**
     * The REST DI group to use.
     *
     * @var string
     */
    public const REST_ROUTES = 'libs.rest_routes';
    /**
     * Defines routes.
     *
     * @return array
     */
    public function define() : array
    {
        return [
            /**
             * The array of routes that will be registered.
             * Add more in other Definers using \Tribe\Libs\Routes\Route_Definer::ROUTES => DI\add( [ ... ] ).
             *
             * ROUTES should extend \Tribe\Libs\Routes\Abstract_Route
             */
            self::ROUTES => DI\add([]),
            /**
             * The array of REST routes that will be registered.
             * Add more in other Definers using \Tribe\Libs\Routes\Route_Definer::REST_ROUTES => DI\add( [ ... ] ).
             *
             * REST ROUTES should extend \Tribe\Libs\Routes\Abstract_REST_Route
             */
            self::REST_ROUTES => DI\add([]),
        ];
    }
}
