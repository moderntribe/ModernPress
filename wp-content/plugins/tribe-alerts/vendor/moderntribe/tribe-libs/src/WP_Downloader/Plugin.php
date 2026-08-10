<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\WP_Downloader;

class Plugin
{
    public function find(string $slug, string $version) : string
    {
        $plugin = $this->get_plugin($slug);
        if ($version === 'latest') {
            $version = $plugin->version;
        }
        return $plugin->versions->{$version} ?? '';
    }
    protected function get_plugin(string $slug) : object
    {
        return \json_decode(\file_get_contents(\sprintf('https://api.wordpress.org/plugins/info/1.0/%s.json', $slug)), \false, 512, \JSON_THROW_ON_ERROR);
    }
}
