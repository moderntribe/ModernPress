<?php

declare (strict_types=1);
namespace Tribe\Alert\Components\Alert;

use Tribe\Alert\Meta\Alert_Meta;
use Tribe\Alert\Meta\Alert_Settings_Meta;
use Tribe\Alert\Post_Types\Alert\Alert;
use Tribe\Alert\Settings\Alert_Settings;
use WP_Post;
class Alert_Model
{
    protected ?WP_Post $active_alert;
    protected Alert_Settings $settings;
    protected \Tribe\Alert\Components\Alert\Alert_Rule_Manager $rule_manager;
    protected \Tribe\Alert\Components\Alert\Color_Options_Manager $color_options;
    public function __construct(Alert_Settings $settings, \Tribe\Alert\Components\Alert\Alert_Rule_Manager $rule_manager, \Tribe\Alert\Components\Alert\Color_Options_Manager $color_options)
    {
        $this->settings = $settings;
        $this->active_alert = $this->assign_current_alert();
        $this->rule_manager = $rule_manager;
        $this->color_options = $color_options;
    }
    public function get_data() : \Tribe\Alert\Components\Alert\Alert_Dto
    {
        if (!$this->can_display()) {
            return new \Tribe\Alert\Components\Alert\Alert_Dto();
        }
        $alert = Alert::factory($this->active_alert->ID);
        $group_cta = (array) $alert->get_meta(Alert_Meta::GROUP_CTA);
        $settings = ['id' => $this->active_alert->ID, 'title' => \get_the_title($this->active_alert), 'content' => $alert->get_meta(Alert_Meta::FIELD_MESSAGE), 'cta' => \array_merge($group_cta, (array) ($group_cta[Alert_Meta::FIELD_CTA_LINK] ?? []))];
        if (\defined('TRIBE_ALERTS_COLOR_OPTIONS') && \true === TRIBE_ALERTS_COLOR_OPTIONS) {
            $settings['color_class'] = $this->color_options->get_color_class($alert->get_meta(Alert_Meta::FIELD_COLOR) ?: '');
        }
        return new \Tribe\Alert\Components\Alert\Alert_Dto($settings);
    }
    protected function assign_current_alert() : ?WP_Post
    {
        $alert = $this->settings->get_setting(Alert_Settings_Meta::FIELD_ACTIVE_ALERT, []);
        return $alert ? \current($alert) : null;
    }
    protected function can_display() : bool
    {
        if (!$this->active_alert || $this->is_alert_published()) {
            return \false;
        }
        $alert = Alert::factory($this->active_alert->ID);
        return $this->rule_manager->should_display((array) $alert->get_meta(Alert_Meta::GROUP_RULES));
    }
    protected function is_alert_published() : bool
    {
        return $this->active_alert && $this->active_alert->post_status && $this->active_alert->post_status !== 'publish';
    }
}
