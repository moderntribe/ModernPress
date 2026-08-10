<?php

namespace Tribe\Alert_Scoped\Tribe\Libs\Cache;

/**
 * Tracks WP events and expires select caches as necessary
 */
class Listener
{
    /** @var Cache */
    protected $cache;
    public function __construct(?Cache $cache = null)
    {
        $this->cache = $cache ?: new Cache();
    }
}
