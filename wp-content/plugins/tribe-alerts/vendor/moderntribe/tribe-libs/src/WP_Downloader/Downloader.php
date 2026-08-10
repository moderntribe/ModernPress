<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\WP_Downloader;

class Downloader
{
    public function download_zip(string $url, string $file_name, string $extract_dir = '/tmp') : string
    {
        $file = \sprintf('%s/%s.zip', $extract_dir, $file_name);
        \file_put_contents($file, \file_get_contents($url));
        return $file;
    }
}
