<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Object_Meta;

use Tribe\Alert_Scoped\DI;
use Tribe\Alert_Scoped\Tribe\Libs\Container\Definer_Interface;
class Object_Meta_Definer implements Definer_Interface
{
    public const GROUPS = 'libs.meta.groups';
    public function define() : array
    {
        return [
            /**
             * The array of groups that will be registered with the Meta Repository.
             * Add more in other Definers using Object_Meta_Definer::GROUPS => DI\add( [ ... ] ).
             *
             * Groups should extend \Tribe\Libs\Object_Meta\Meta_Group
             */
            self::GROUPS => DI\add([]),
            Meta_Repository::class => DI\create()->constructor(DI\get(self::GROUPS)),
        ];
    }
}
