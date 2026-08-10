<?php

declare (strict_types=1);
namespace Tribe\Alert\Resources;

class Manifest_Loader
{
    /**
     * The path to the Laravel Mix generated mix-manifest.json.
     */
    protected string $manifest_path;
    /**
     * The URI to the dist folder.
     */
    protected string $dist_uri;
    /**
     * Cached value of the manifest.json.
     *
     * @var string[]|null
     */
    protected ?array $cache = null;
    public function __construct(string $manifest_path, string $dist_uri)
    {
        $this->manifest_path = $manifest_path;
        $this->dist_uri = $dist_uri;
    }
    /**
     * An array of asset keys and their relative URL path.
     *
     * @return string[]
     */
    public function get_manifest() : array
    {
        if (\is_array($this->cache)) {
            return $this->cache;
        }
        if (!\is_readable($this->manifest_path)) {
            $this->cache = [];
            return $this->cache;
        }
        $scripts = \json_decode(\file_get_contents($this->manifest_path));
        // Prefix paths with dist URI, $path has a starting /.
        foreach ($scripts as $name => $path) {
            $this->cache[$name] = \sprintf('%s%s', $this->dist_uri, $path);
        }
        return $this->cache;
    }
}
