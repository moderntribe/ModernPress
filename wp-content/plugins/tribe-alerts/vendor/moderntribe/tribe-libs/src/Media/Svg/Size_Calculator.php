<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Media\Svg;

class Size_Calculator
{
    private $original_width;
    private $original_height;
    private $original_aspect;
    private $unreasonably_large_value = 9000;
    public function __construct(int $original_width, int $original_height)
    {
        $this->original_width = $original_width ?: $original_height;
        $this->original_height = $original_height ?: $original_width;
        $this->original_aspect = $this->original_height ? $this->original_width / $this->original_height : 1;
    }
    /**
     * Scale the dimensions to the largest size that will fit within the
     * target dimensions
     *
     * @param int $target_width
     * @param int $target_height
     *
     * @return int[] The width and height of the resulting image
     */
    public function scale(int $target_width, int $target_height) : array
    {
        $target_width = $this->normalize($target_width);
        $target_height = $this->normalize($target_height);
        if ($target_width === 0 && $target_height === 0) {
            return [$this->original_width, $this->original_height];
        }
        if ($target_width === 0) {
            $target_aspect = $this->original_aspect;
            $target_width = (int) \round($target_height * $target_aspect);
        } elseif ($target_height === 0) {
            $target_aspect = $this->original_aspect;
            $target_height = (int) \round($target_width / $target_aspect);
        } else {
            $target_aspect = $target_width / $target_height;
        }
        if ($this->original_aspect > $target_aspect) {
            // scale based on width
            $ratio = $this->original_width ? $target_width / $this->original_width : 1;
        } else {
            // scale based on height
            $ratio = $this->original_height ? $target_height / $this->original_height : 1;
        }
        $scaled_width = (int) \round($this->original_width * $ratio);
        $scaled_height = (int) \round($this->original_height * $ratio);
        return [$scaled_width, $scaled_height];
    }
    /**
     * Scale the image to fit within the target dimensions, padding
     * as necessary to fill the space.
     *
     * If either target dimension is unspecified, delegates to scale().
     *
     * @param int $target_width
     * @param int $target_height
     *
     * @return int[] The width and height of the resulting image
     */
    public function crop(int $target_width, int $target_height) : array
    {
        $target_width = $this->normalize($target_width);
        $target_height = $this->normalize($target_height);
        if ($target_width === 0 || $target_height === 0) {
            return $this->scale($target_width, $target_height);
        }
        return [$target_width, $target_height];
    }
    private function normalize(int $value) : int
    {
        if ($value >= $this->unreasonably_large_value) {
            return 0;
        }
        return $value;
    }
}
