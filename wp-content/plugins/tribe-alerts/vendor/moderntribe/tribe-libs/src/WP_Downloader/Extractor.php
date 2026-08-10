<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\WP_Downloader;

use Tribe\Alert_Scoped\PhpZip\Exception\ZipException;
use Tribe\Alert_Scoped\PhpZip\ZipFile;
class Extractor
{
    protected string $project_root;
    protected ZipFile $zip;
    public function __construct(ZipFile $zip, string $project_root)
    {
        $this->zip = $zip;
        $this->project_root = $project_root;
    }
    public function unzip(string $file, string $destination) : void
    {
        if (!\str_starts_with($destination, '/')) {
            $destination = \realpath($this->project_root) . '/' . $destination;
        }
        try {
            $this->zip->openFile($file)->extractTo($destination);
        } catch (ZipException $e) {
            $this->zip->close();
            throw $e;
        } finally {
            $this->zip->close();
        }
        // Clean up zip after successful extraction.
        \unlink($file);
    }
}
