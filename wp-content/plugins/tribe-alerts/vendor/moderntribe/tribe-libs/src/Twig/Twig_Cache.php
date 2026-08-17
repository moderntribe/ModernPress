<?php

namespace Tribe\Alert_Scoped\Tribe\Libs\Twig;

use Tribe\Alert_Scoped\Twig\Cache\FilesystemCache;
class Twig_Cache extends FilesystemCache
{
    protected $path;
    public function __construct($path, $options = 0)
    {
        $this->path = $path;
        parent::__construct($path, $options);
    }
    public function generateKey(string $name, string $className) : string
    {
        $hash = \hash('sha256', $className);
        return $this->path . $hash[0] . $hash[1] . '/' . $hash . '.html';
    }
}
