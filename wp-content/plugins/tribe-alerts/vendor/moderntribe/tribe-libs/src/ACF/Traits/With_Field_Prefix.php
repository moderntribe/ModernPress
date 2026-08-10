<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\ACF\Traits;

use InvalidArgumentException;
/**
 * Additional Block Field functionality.
 *
 * @mixin \Tribe\Libs\ACF\Block_Config
 */
trait With_Field_Prefix
{
    /**
     * Get an ACF field with the proper prefix.
     *
     * @param  string  $name             The ACF field name.
     * @param  bool    $has_name_prefix  Whether the section was defined with a `self::NAME . '_' .` prefix.
     *
     * @throws InvalidArgumentException
     *
     * @see \Tribe\Libs\ACF\Field
     * @see \Tribe\Libs\ACF\Field_Group
     * @see \Tribe\Libs\ACF\Repeater
     *
     * @return string The prefixed field, group or repeater key.
     */
    protected function get_field_key(string $name, bool $has_name_prefix = \true) : string
    {
        if ($has_name_prefix) {
            $this->validate_constants();
            return \sprintf('%s_%s_%s', 'field', self::NAME, $name);
        }
        return \sprintf('%s_%s', 'field', $name);
    }
    /**
     * Get a section key. Unfortunately the prefix in the Field_Section class includes
     * an underscore for some reason, which creates a double underscored field key.
     *
     * In addition to this, we don't seem to prefix sections with the block's name like all other fields.
     *
     * @param  string  $name             The name of the section.
     * @param  bool    $has_name_prefix  Whether the section was defined with a `self::NAME . '_' .` prefix.
     *
     * @throws InvalidArgumentException
     *
     * @see \Tribe\Libs\ACF\Field_Section
     *
     * @return string The prefixed section key.
     */
    protected function get_section_key(string $name, bool $has_name_prefix = \false) : string
    {
        if ($has_name_prefix) {
            $this->validate_constants();
            return \sprintf('%s__%s_%s', 'section', self::NAME, $name);
        }
        return \sprintf('%s__%s', 'section', $name);
    }
    /**
     * Get a prefixed ACF Layout field key.
     *
     * @param  string  $name    The name of the layout.
     * @param  bool    $has_name_prefix Whether the section was defined with a `self::NAME . '_' .` prefix.
     *
     * @throws InvalidArgumentException
     *
     * @see \Tribe\Libs\ACF\Layout
     *
     * @return string The prefixed key.
     */
    protected function get_layout_key(string $name, bool $has_name_prefix = \true) : string
    {
        if ($has_name_prefix) {
            $this->validate_constants();
            return \sprintf('%s_%s_%s', 'layout', self::NAME, $name);
        }
        return \sprintf('%s_%s', 'layout', $name);
    }
    /**
     * Ensure this trait has the required constants to function.
     *
     * @throws \InvalidArgumentException
     */
    protected function validate_constants() : void
    {
        if (!\defined('self::NAME')) {
            throw new InvalidArgumentException('Cannot find the NAME constant. This trait should be used in an extended \\Tribe\\Libs\\ACF\\Block_Config class.');
        }
    }
}
