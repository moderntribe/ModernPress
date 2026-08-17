<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\WP_Downloader;

use Tribe\Alert_Scoped\Symfony\Component\Console\Output\OutputInterface;
class Installer
{
    protected Downloader $downloader;
    protected Extractor $extractor;
    public function __construct(Downloader $downloader, Extractor $extractor)
    {
        $this->downloader = $downloader;
        $this->extractor = $extractor;
    }
    /**
     * @param  string                                             $url          The URL to the zip file.
     * @param  string                                             $file_name    The file name, without extension.
     * @param  string                                             $destination  The destination directory to extract the zip to.
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output       Symfony command output.
     *
     * @throws \PhpZip\Exception\ZipException
     * @return bool
     */
    public function install(string $url, string $file_name, string $destination, OutputInterface $output) : bool
    {
        $output->writeln(\sprintf('Downloading %s...', $url));
        $file = $this->downloader->download_zip($url, $file_name);
        $output->writeln(\sprintf('Extracting %s to %s...', $file, $destination));
        $this->extractor->unzip($file, $destination);
        return \true;
    }
}
