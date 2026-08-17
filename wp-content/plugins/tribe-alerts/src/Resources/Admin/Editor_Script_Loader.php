<?php

declare (strict_types=1);
namespace Tribe\Alert\Resources\Admin;

use Tribe\Alert\Resources\Loader;
/**
 * Block editor script & style loader.
 */
class Editor_Script_Loader extends Loader
{
    /**
     * @action enqueue_block_editor_assets
     */
    public function enqueue() : void
    {
        \wp_enqueue_script('tribe-alerts-editor-js', $this->manifest_loader->get_manifest()['/js/admin/editor.js']);
        \wp_enqueue_style('tribe-alerts-editor-css', $this->manifest_loader->get_manifest()['/css/admin/editor.css']);
    }
}
