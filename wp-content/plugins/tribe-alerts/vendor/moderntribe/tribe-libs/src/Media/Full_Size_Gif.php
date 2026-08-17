<?php

namespace Tribe\Alert_Scoped\Tribe\Libs\Media;

class Full_Size_Gif
{
    /**
     * @param array|false $data
     * @param int         $attachment_id
     * @param string      $size
     *
     * @return array|false
     * @filter image_downsize
     */
    public function full_size_only_gif($data, $attachment_id, $size)
    {
        if ($size === 'full') {
            return $data;
        }
        $meta = \wp_get_attachment_metadata($attachment_id);
        if (isset($meta['file']) && $this->is_gif($meta['file'])) {
            return \image_downsize($attachment_id, 'full');
        }
        return $data;
    }
    /**
     * @param string $src
     *
     * @return bool
     */
    public function is_gif($src) : bool
    {
        return \pathinfo($src, \PATHINFO_EXTENSION) === 'gif';
    }
}
