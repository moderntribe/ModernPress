<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Field_Models\Models;

use Tribe\Alert_Scoped\Tribe\Libs\Field_Models\Field_Model;
/**
 * Represents a color swatch.
 *
 * @link https://developer.wordpress.org/block-editor/how-to-guides/themes/theme-support/#block-color-palettes
 */
class Swatch extends Field_Model
{
    /**
     * The color, in a supported format.
     *
     * @example #ffffff, rgb(255,0,0), rgba(255,0,0, 1), hsl(0,100%,50%)
     */
    public string $color;
    /**
     * The i18n translated color label.
     *
     * @example __( 'White', 'tribe' )
     */
    public string $name;
    /**
     * The uniquely named slug for this swatch.
     *
     * @example white
     */
    public string $slug;
}
