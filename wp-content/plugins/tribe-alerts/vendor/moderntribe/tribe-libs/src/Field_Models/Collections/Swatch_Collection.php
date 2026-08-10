<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Field_Models\Collections;

use Tribe\Alert_Scoped\Tribe\Libs\Field_Models\DTO\Data_Transfer_Object_Collection;
use Tribe\Alert_Scoped\Tribe\Libs\Field_Models\Models\Swatch;
/**
 * A collection of Swatch objects/aka a color palette.
 */
class Swatch_Collection extends Data_Transfer_Object_Collection
{
    public static function create(array $swatches) : Swatch_Collection
    {
        return new static(Swatch::arrayOf($swatches));
    }
    public function current() : ?Swatch
    {
        return parent::current();
    }
    /**
     * Retrieve a Swatch by slug
     *
     * @param mixed $offset The slug, e.g. black.
     *
     * @return \Tribe\Libs\Field_Models\Models\Swatch|null
     */
    public function offsetGet($offset) : ?Swatch
    {
        return parent::offsetGet($offset);
    }
    /**
     * Return a subset of the theme color swatches from all available swatches.
     *
     * @param  string[]  $color_slugs
     *
     * @return \Tribe\Libs\Field_Models\Collections\Swatch_Collection
     */
    public function get_subset(array $color_slugs = []) : Swatch_Collection
    {
        return self::create(\array_intersect_key($this->toArray(), \array_flip($color_slugs)));
    }
    /**
     * Formats the colors as `[ 'name' => <name>, 'slug' => <slug>, 'color' => <color> ]` and adds
     * proper escaping.
     *
     * @link https://developer.wordpress.org/block-editor/developers/themes/theme-support/#block-color-palettes
     *
     * @return array<array{name: string, slug: string, color: string}>
     */
    public function format_for_blocks() : array
    {
        $swatches = $this->toArray();
        return \array_reduce($swatches, static function (array $carry, array $swatch) {
            $swatch['name'] = \esc_attr($swatch['name']);
            $carry[] = $swatch;
            return $carry;
        }, []);
    }
    /**
     * Format a swatch collection for the ACF swatch field and escape the label.
     *
     * @example `[ <color> => <name> ]`
     *
     * @return array<string, string>
     */
    public function format_for_acf() : array
    {
        $swatches = $this->toArray();
        return \array_reduce($swatches, static function (array $carry, array $swatch) {
            $carry[$swatch['color']] = \esc_html($swatch['name']);
            return $carry;
        }, []);
    }
    /**
     * Return a swatch by an ACF Swatch Field value.
     *
     * @param  string  $color_format  The swatch value, e.g. #FFFFFF, rgba(255,0,0, 1) etc...
     *
     * @return \Tribe\Libs\Field_Models\Models\Swatch|null
     */
    public function get_by_value(string $color_format = '') : ?Swatch
    {
        foreach ($this->iterator as $swatch) {
            if ($swatch->color === $color_format) {
                return $swatch;
            }
        }
        $this->rewind();
        return null;
    }
}
