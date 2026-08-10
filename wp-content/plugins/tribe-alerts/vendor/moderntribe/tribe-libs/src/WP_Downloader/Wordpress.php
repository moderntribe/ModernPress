<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\WP_Downloader;

class Wordpress
{
    public function find_version(string $version) : ?object
    {
        $releases = $this->get_releases();
        if ($version === 'latest') {
            return \reset($releases);
        }
        foreach ($releases as $release) {
            if ($release->version === $version) {
                return $release;
            }
        }
        return null;
    }
    protected function get_releases() : array
    {
        return \json_decode(\file_get_contents('https://api.wordpress.org/core/version-check/1.7/'), \false, 512, \JSON_THROW_ON_ERROR)->offers;
    }
}
