<?php

declare (strict_types=1);
namespace Tribe\Alert\Meta;

use Tribe\Alert\Post_Types\Alert\Alert;
use Tribe\Alert_Scoped\Tribe\Libs\ACF;
class Alert_Settings_Meta extends ACF\ACF_Meta_Group
{
    public const NAME = 'alert_settings';
    public const FIELD_ACTIVE_ALERT = 'alert_active';
    public function get_keys() : array
    {
        return [self::FIELD_ACTIVE_ALERT];
    }
    /**
     * @param string|int $key
     * @param string|int $post_id
     *
     * @return mixed|null
     */
    public function get_value($key, $post_id = 'option')
    {
        return \in_array($key, $this->get_keys(), \true) ? \get_field($key, $post_id) : null;
    }
    protected function get_group_config() : array
    {
        $group = new ACF\Group(self::NAME, $this->object_types);
        $group->set('title', \esc_html__('Alert Settings', 'tribe-alerts'));
        $group->add_field($this->get_active_alert_field());
        return $group->get_attributes();
    }
    /**
     * The Active Alert Field. The selected post is the single
     * active alert to display.
     */
    private function get_active_alert_field() : ACF\Field
    {
        $field = new ACF\Field(self::NAME . '_' . self::FIELD_ACTIVE_ALERT);
        $field->set_attributes([
            'label' => \esc_html__('Select the active alert to display', 'tribe-alerts'),
            'name' => self::FIELD_ACTIVE_ALERT,
            'type' => 'relationship',
            'instructions' => \esc_html__('', 'tribe-alerts'),
            'required' => \false,
            'conditional_logic' => \false,
            'post_type' => [Alert::NAME],
            'filters' => ['search'],
            'min' => 0,
            // (int)
            'max' => 1,
            // (int)
            'return_format' => 'object',
        ]);
        return $field;
    }
}
