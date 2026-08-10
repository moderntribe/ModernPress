<?php

declare (strict_types=1);
namespace Tribe\Alert\Resources\Theme;

use Tribe\Alert\Resources\Loader;
/**
 * Front-end script & style loader.
 */
class Script_Loader extends Loader
{
    /**
     * @action wp_enqueue_scripts
     */
    public function enqueue() : void
    {
        \wp_enqueue_script('tribe-alerts-index-js', $this->manifest_loader->get_manifest()['/js/theme/index.js']);
        \wp_enqueue_style('tribe-alerts-index-css', $this->manifest_loader->get_manifest()['/css/theme/main.css']);
    }
}
