<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Media\Svg\Store;

use RuntimeException;
use Tribe\Alert_Scoped\Tribe\Libs\Media\Svg\Store\Contracts\Svg_Store;
class Svg_Store_Handler
{
    protected Svg_Store $svg_store;
    public function __construct(Svg_Store $svg_store)
    {
        $this->svg_store = $svg_store;
    }
    /**
     * Store SVG markup for retrieval later.
     *
     * @filter update_attached_file
     *
     * @param string $file          The path to the attached file to update.
     * @param int    $attachment_id The attachment/post ID.
     *
     * @throws \RuntimeException
     */
    public function store(string $file, int $attachment_id) : string
    {
        $mime = \get_post_mime_type($attachment_id);
        if ($mime !== 'image/svg+xml') {
            return $file;
        }
        if (!$this->svg_store->save($file, $attachment_id)) {
            throw new RuntimeException(\sprintf('Unable to save SVG markup from %s to attachment ID: %d', $file, $attachment_id));
        }
        return $file;
    }
}
