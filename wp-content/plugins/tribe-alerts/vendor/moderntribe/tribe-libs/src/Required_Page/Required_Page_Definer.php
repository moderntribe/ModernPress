<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Required_Page;

use Tribe\Alert_Scoped\Tribe\Libs\Container\Definer_Interface;
class Required_Page_Definer implements Definer_Interface
{
    public const PAGES = 'libs.required_page.pages';
    public function define() : array
    {
        return [self::PAGES => \DI\add([])];
    }
}
