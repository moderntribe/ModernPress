<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\ACF;

use InvalidArgumentException;
use Tribe\Alert_Scoped\Tribe\Libs\ACF\Traits\With_Field_Prefix;
abstract class Block_Config
{
    use With_Field_Prefix;
    public const NAME = '';
    /**
     * @var \Tribe\Libs\ACF\Field[]
     */
    protected $fields = [];
    /**
     * @var \Tribe\Libs\ACF\Block
     */
    protected $block;
    public function __construct()
    {
        $this->add_block();
        $this->add_fields();
    }
    /**
     * Add an ACF Block to the Block_Config
     *
     * @see \Tribe\Libs\ACF\Block_Config::set_block()
     *
     * @return void
     */
    public abstract function add_block();
    /**
     * Override this method in your subclass to add fields.
     *
     * @TODO we should make this an abstract in the next major version of tribe-libs.
     *
     * @see \Tribe\Libs\ACF\Block_Config::add_section()
     * @see \Tribe\Libs\ACF\Block_Config::add_field()
     *
     * @return void
     */
    protected function add_fields()
    {
    }
    /**
     * @param Field_Section $section
     *
     * @return Field_Section
     */
    public function add_section(Field_Section $section) : Field_Section
    {
        $this->fields[] = $section;
        return $section;
    }
    /**
     * @param  \Tribe\Libs\ACF\Block  $block
     *
     * @return $this
     */
    public function set_block(Block $block) : Block_Config
    {
        $attr = $block->get_attributes();
        if (empty($attr['render_callback']) && empty($attr['render_template'])) {
            $block->set('render_callback', function (...$args) {
                \do_action('tribe/project/block/render', ...$args);
            });
        }
        $this->block = $block;
        return $this;
    }
    public function get_block() : ?Block
    {
        return $this->block;
    }
    /**
     * Append a field object to the block.
     *
     * @param  \Tribe\Libs\ACF\Field  $field
     *
     * @return $this
     */
    public function add_field(Field $field) : Block_Config
    {
        $this->fields[] = $field;
        return $this;
    }
    /**
     * Get the currently set field objects.
     *
     * @return \Tribe\Libs\ACF\Field[]
     */
    public function get_fields() : array
    {
        return $this->fields;
    }
    /**
     * Allow fields to be mutated after run time for block middleware
     * etc...
     *
     * @param  \Tribe\Libs\ACF\Field[]  $fields
     *
     * @return $this
     */
    public function set_fields(array $fields) : Block_Config
    {
        $this->fields = $fields;
        return $this;
    }
    public function get_field_group() : Group
    {
        if (static::NAME == '') {
            throw new InvalidArgumentException('Block requires a NAME constant in ' . static::class);
        }
        $group = new Group(static::NAME, ['block' => [static::NAME]]);
        foreach ($this->fields as $field) {
            $group->add_field($field);
        }
        return $group;
    }
}
