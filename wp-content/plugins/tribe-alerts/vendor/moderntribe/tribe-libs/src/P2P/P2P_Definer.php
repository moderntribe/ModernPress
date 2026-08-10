<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\P2P;

use Tribe\Alert_Scoped\DI;
use Tribe\Alert_Scoped\Tribe\Libs\Container\Definer_Interface;
class P2P_Definer implements Definer_Interface
{
    public const RELATIONSHIPS = 'libs.p2p.relationships';
    public const ADMIN_SEARCH_FILTERS = 'libs.p2p.admin_search_filters';
    public const TITLE_FILTER_RELATIONSHIPS = 'libs.p2p.title_filter_relationships';
    public function define() : array
    {
        return [
            /**
             * The array of relationships that will be registered.
             * Add more in other Definers using P2P_Definer::RELATIONSHIPS => DI\add( [ ... ] ).
             *
             * Relationships should extend \Tribe\Libs\P2P\Relationship
             */
            self::RELATIONSHIPS => DI\add([]),
            /**
             * The array of admin search filters to set up.
             * Add more in other Definers using:
             *
             * P2P_Definer::ADMIN_SEARCH_FILTERS => DI\add( [
             *   DI\create( Admin_Search_Filtering::class )->constructor( DI\get( My_Relationship::class ), 'both' )
             * ] )
             */
            self::ADMIN_SEARCH_FILTERS => DI\add([]),
            /**
             * The array of admin title filters to set up.
             * Add more in other Definers using:
             *
             * P2P_Definer::TITLE_FILTER_RELATIONSHIPS => DI\add( [
             *   DI\get( My_Relationship::class )
             * ] )
             *
             */
            self::TITLE_FILTER_RELATIONSHIPS => DI\add([]),
            Titles_Filter::class => DI\create()->constructor(DI\get(self::TITLE_FILTER_RELATIONSHIPS)),
        ];
    }
}
