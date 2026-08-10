<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Args\MetaQuery;

/**
 * Methods for any query class that supports meta queries.
 */
interface WithArgs
{
    public function setMetaQuery(Query $meta_query) : void;
}
