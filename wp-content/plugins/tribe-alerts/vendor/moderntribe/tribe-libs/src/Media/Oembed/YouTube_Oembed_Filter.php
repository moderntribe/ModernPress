<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Media\Oembed;

class YouTube_Oembed_Filter
{
    /**
     * Replace all youtube.com/embed URI's with youtube-nocookie.com/embed URI's.
     *
     * @filter oembed_dataparse
     *
     * @param string $html The returned oEmbed HTML, generally an iframe.
     */
    public function force_youtube_no_cookie_embed(string $html) : string
    {
        return \str_ireplace('youtube.com/embed', 'youtube-nocookie.com/embed', $html);
    }
}
