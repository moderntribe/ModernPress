<?php

declare (strict_types=1);
namespace Tribe\Alert\Resources\Admin;

use Tribe\Alert\Resources\Loader;
/**
 * wp-admin/dashboard script & style loader.
 */
class Admin_Script_Loader extends Loader
{
    /**
     * @action admin_enqueue_scripts
     */
    public function enqueue() : void
    {
        \wp_enqueue_script('tribe-alerts-admin-index-js', $this->manifest_loader->get_manifest()['/js/admin/index.js']);
        \wp_enqueue_style('tribe-alerts-admin-main-css', $this->manifest_loader->get_manifest()['/css/admin/main.css']);
    }
}
