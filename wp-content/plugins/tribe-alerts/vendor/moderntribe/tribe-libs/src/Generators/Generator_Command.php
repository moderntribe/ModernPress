<?php

namespace Tribe\Alert_Scoped\Tribe\Libs\Generators;

use Tribe\Alert_Scoped\Tribe\Libs\CLI\Command;
abstract class Generator_Command extends Command
{
    protected $file_system = null;
    protected $templates_path = '';
    protected $src_path = '';
    /**
     * Generator_Command constructor.
     *
     * @param File_System $file_system
     * @param string      $src_path Path to the core plugin's src directory
     */
    public function __construct(File_System $file_system, $src_path)
    {
        $this->file_system = $file_system;
        $this->templates_path = \trailingslashit(__DIR__) . 'templates/';
        $this->src_path = \trailingslashit($src_path);
        parent::__construct();
    }
    /**
     * converts a multi-word lowercase _ separated slug in
     * multi-word upper case first format.
     *
     * multi_word_slug becomes Multi_Word_Slug
     *
     * @param string $slug lowercase words separated by _.
     *
     * @return string
     */
    public function ucwords($slug)
    {
        $uc_words = \array_map(fn($word) => \ucfirst($word), \explode('_', $slug));
        return \implode('_', $uc_words);
    }
    protected function sanitize_slug($args)
    {
        [$slug] = $args;
        return \str_replace('-', '_', \sanitize_title($slug));
    }
}
