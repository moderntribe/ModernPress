<?php

declare (strict_types=1);
namespace Tribe\Alert\Resources;

/**
 * Abstract Script and Style Loader.
 */
abstract class Loader
{
    protected \Tribe\Alert\Resources\Manifest_Loader $manifest_loader;
    public abstract function enqueue() : void;
    public function __construct(\Tribe\Alert\Resources\Manifest_Loader $manifest_loader)
    {
        $this->manifest_loader = $manifest_loader;
    }
}
