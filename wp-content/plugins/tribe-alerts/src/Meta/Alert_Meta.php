<?php

declare (strict_types=1);
namespace Tribe\Alert\Meta;

use Tribe\Alert\Components\Alert\Color_Options_Manager;
use Tribe\Alert\Post_Types\Alert\Alert;
use Tribe\Alert\Traits\With_Field_Prefix;
use Tribe\Alert_Scoped\Tribe\Libs\ACF;
use Tribe\Alert_Scoped\Tribe\Libs\ACF\Field;
use WP_Post_Type;
use WP_Taxonomy;
class Alert_Meta extends ACF\ACF_Meta_Group
{
    use With_Field_Prefix;
    public const NAME = 'tribe_alert_meta';
    public const MAX_POSTS = 25;
    public const FIELD_MESSAGE = 'alert_message';
    public const GROUP_CTA = 'alert_cta';
    public const FIELD_CTA_LINK = 'cta';
    public const FIELD_CTA_ADD_ARIA_LABEL = 'add_aria_label';
    public const FIELD_CTA_ARIA_LABEL = 'aria_label';
    public const GROUP_RULES = 'alert_rules';
    public const FIELD_RULES_DISPLAY_TYPE = 'display_type';
    public const FIELD_RULES_INCLUDE_PAGES = 'include_pages';
    public const FIELD_RULES_EXCLUDE_PAGES = 'exclude_pages';
    public const FIELD_RULES_APPLY_TO_FRONT_PAGE = 'apply_to_front_page';
    public const FIELD_TAXONOMY_ARCHIVES = 'taxonomy_archives';
    public const FIELD_POST_TYPE_ARCHIVES = 'post_type_archives';
    public const FIELD_COLOR = 'alert_color';
    public const OPTION_EVERY_PAGE = 'every_page';
    public const OPTION_INCLUDE = 'include';
    public const OPTION_EXCLUDE = 'exclude';
    protected Color_Options_Manager $color_options;
    public function __construct(array $object_types, Color_Options_Manager $color_options)
    {
        parent::__construct($object_types);
        $this->color_options = $color_options;
    }
    public function get_keys() : array
    {
        return [self::FIELD_MESSAGE, self::GROUP_CTA, self::GROUP_RULES, self::FIELD_COLOR];
    }
    protected function get_group_config() : array
    {
        $group = new ACF\Group(self::NAME, $this->object_types);
        $group->set('title', \esc_html__('Alert Settings', 'tribe-alerts'));
        $group->add_field($this->get_alert_message_field());
        $group->add_field($this->get_cta_group());
        $group->add_field($this->get_rules_group());
        if (\defined('TRIBE_ALERTS_COLOR_OPTIONS') && \true === TRIBE_ALERTS_COLOR_OPTIONS) {
            $group->add_field($this->get_colors_field());
        }
        return $group->get_attributes();
    }
    /**
     * The alert message / description.
     */
    private function get_alert_message_field() : ACF\Field
    {
        $field = new ACF\Field(self::NAME . '_' . self::FIELD_MESSAGE);
        $field->set_attributes([
            'label' => \esc_html__('Message', 'tribe-alerts'),
            'name' => self::FIELD_MESSAGE,
            'type' => 'textarea',
            'instructions' => \esc_html__('The message to display below the title.', 'tribe-alerts'),
            'required' => \false,
            'conditional_logic' => \false,
            'default_value' => '',
            'placeholder' => \esc_attr__('', 'tribe-alerts'),
            'maxlength' => '',
            // (int)
            'rows' => '',
            // (int)
            'new_lines' => '',
        ]);
        return $field;
    }
    private function get_cta_group() : ACF\Field_Group
    {
        $group = new ACF\Field_Group(self::NAME . '_' . self::GROUP_CTA, ['label' => \esc_html__('Call to Action', 'tribe-alerts'), 'name' => self::GROUP_CTA, 'layout' => 'block']);
        $fields = [];
        $fields[] = new Field(self::GROUP_CTA . '_' . self::FIELD_CTA_LINK, ['label' => \esc_html__('Link', 'tribe-alerts'), 'name' => self::FIELD_CTA_LINK, 'type' => 'link', 'wrapper' => ['class' => 'tribe-acf-hide-label']]);
        $fields[] = new Field(self::GROUP_CTA . '_' . self::FIELD_CTA_ADD_ARIA_LABEL, ['label' => \esc_html__('Add Screen Reader Text', 'tribe-alerts'), 'name' => self::FIELD_CTA_ADD_ARIA_LABEL, 'type' => 'true_false', 'message' => \esc_html__('Add Screen Reader Text', 'tribe-alerts'), 'wrapper' => ['class' => 'tribe-acf-hide-label']]);
        $fields[] = new Field(self::GROUP_CTA . '_' . self::FIELD_CTA_ARIA_LABEL, ['label' => \esc_html__('Screen Reader Label', 'tribe-alerts'), 'instructions' => \esc_html__('A custom label for screen readers if the button\'s action or purpose isn\'t easily identifiable', 'tribe-alerts'), 'name' => self::FIELD_CTA_ARIA_LABEL, 'type' => 'text', 'conditional_logic' => [[['field' => $this->get_key_with_prefix(self::FIELD_CTA_ADD_ARIA_LABEL, self::GROUP_CTA), 'operator' => '==', 'value' => 1]]]]);
        foreach ($fields as $field) {
            $group->add_field($field);
        }
        return $group;
    }
    private function get_rules_group() : ACF\Field_Group
    {
        $group = new ACF\Field_Group(self::NAME . '_' . self::GROUP_RULES, ['label' => \esc_html__('Display Rules', 'tribe-alerts'), 'name' => self::GROUP_RULES, 'layout' => 'block']);
        $fields = [];
        $fields[] = new Field(self::GROUP_RULES . '_' . self::FIELD_RULES_DISPLAY_TYPE, ['label' => \esc_html__('Select a rule', 'tribe-alerts'), 'name' => self::FIELD_RULES_DISPLAY_TYPE, 'type' => 'radio', 'choices' => [self::OPTION_EVERY_PAGE => \esc_html__('Show everywhere', 'tribe-alerts'), self::OPTION_INCLUDE => \esc_html__('Show only on specified pages', 'tribe-alerts'), self::OPTION_EXCLUDE => \esc_html__('Exclude from specific pages', 'tribe-alerts')], 'default_value' => self::OPTION_EVERY_PAGE]);
        $fields[] = new Field(self::GROUP_RULES . '_' . self::FIELD_RULES_APPLY_TO_FRONT_PAGE, ['label' => \esc_html__('Apply the selected rule to the Front Page', 'tribe-alerts'), 'name' => self::FIELD_RULES_APPLY_TO_FRONT_PAGE, 'type' => 'true_false', 'instructions' => \sprintf('%s<a href="%s">%s</a>%s', \esc_html__('Regardless of the configuration in ', 'tribe-alerts'), \esc_url(\admin_url('options-reading.php')), \esc_html__('Settings > Reading', 'tribe-alerts'), \esc_html__(', always apply these rules to the front page', 'tribe-alerts')), 'ui' => \true, 'default_value' => \false, 'conditional_logic' => [[['field' => $this->get_key_with_prefix(self::FIELD_RULES_DISPLAY_TYPE, self::GROUP_RULES), 'operator' => '!=', 'value' => self::OPTION_EVERY_PAGE]]]]);
        $fields[] = new Field(self::GROUP_RULES . '_' . self::FIELD_RULES_INCLUDE_PAGES, [
            'label' => \esc_html__('Select pages where the alert will appear', 'tribe-alerts'),
            'name' => self::FIELD_RULES_INCLUDE_PAGES,
            'type' => 'relationship',
            'instructions' => \sprintf(\esc_html__('Select up to %d posts', 'tribe-alerts'), (int) \apply_filters('tribe/alerts/meta/max_posts', self::MAX_POSTS)),
            'required' => \false,
            'conditional_logic' => [[['field' => $this->get_key_with_prefix(self::FIELD_RULES_DISPLAY_TYPE, self::GROUP_RULES), 'operator' => '==', 'value' => self::OPTION_INCLUDE]]],
            'post_type' => $this->get_allowed_post_types(),
            'filters' => ['search', 'post_type', 'taxonomy'],
            'min' => 0,
            // (int)
            'max' => (int) \apply_filters('tribe/alerts/meta/max_posts', self::MAX_POSTS),
            // (int)
            'return_format' => 'object',
        ]);
        $fields[] = new Field(self::GROUP_RULES . '_' . self::FIELD_RULES_EXCLUDE_PAGES, [
            'label' => \esc_html__('Will appear on every page but the following selected pages', 'tribe-alerts'),
            'name' => self::FIELD_RULES_EXCLUDE_PAGES,
            'type' => 'relationship',
            'instructions' => \sprintf(\esc_html__('Select up to %d posts', 'tribe-alerts'), (int) \apply_filters('tribe/alerts/meta/max_posts', self::MAX_POSTS)),
            'required' => \false,
            'conditional_logic' => [[['field' => $this->get_key_with_prefix(self::FIELD_RULES_DISPLAY_TYPE, self::GROUP_RULES), 'operator' => '==', 'value' => self::OPTION_EXCLUDE]]],
            'post_type' => $this->get_allowed_post_types(),
            'filters' => ['search', 'post_type', 'taxonomy'],
            'min' => 0,
            // (int)
            'max' => (int) \apply_filters('tribe/alerts/meta/max_posts', self::MAX_POSTS),
            // (int)
            'return_format' => 'object',
        ]);
        $fields[] = new Field(self::GROUP_RULES . '_' . self::FIELD_TAXONOMY_ARCHIVES, [
            'label' => \esc_html__('Apply the selected rule to the following Taxonomy Archives:', 'tribe-alerts'),
            'name' => self::FIELD_TAXONOMY_ARCHIVES,
            'type' => 'select',
            'required' => \false,
            'conditional_logic' => [[['field' => $this->get_key_with_prefix(self::FIELD_RULES_DISPLAY_TYPE, self::GROUP_RULES), 'operator' => '!=', 'value' => self::OPTION_EVERY_PAGE]]],
            'default_value' => [],
            'choices' => \array_reduce(\get_taxonomies(['public' => \true], 'objects'), static function (array $taxonomies, WP_Taxonomy $tax) {
                $taxonomies[$tax->name] = $tax->labels->name;
                return $taxonomies;
            }, []),
            'multiple' => \true,
            'ui' => \true,
            'ajax' => \true,
            // lazy load
            'return_format' => 'value',
        ]);
        $fields[] = new Field(self::GROUP_RULES . '_' . self::FIELD_POST_TYPE_ARCHIVES, [
            'label' => \esc_html__('Apply the selected rule to the following Post Type Archives:', 'tribe-alerts'),
            'name' => self::FIELD_POST_TYPE_ARCHIVES,
            'type' => 'select',
            'required' => \false,
            'conditional_logic' => [[['field' => $this->get_key_with_prefix(self::FIELD_RULES_DISPLAY_TYPE, self::GROUP_RULES), 'operator' => '!=', 'value' => self::OPTION_EVERY_PAGE]]],
            'default_value' => [],
            'choices' => \array_reduce(\get_post_types(['public' => \true, 'publicly_queryable' => \true, 'has_archive' => \true], 'objects'), static function (array $post_types, WP_Post_Type $post_type) {
                $post_types[$post_type->name] = $post_type->labels->name;
                return $post_types;
            }, []),
            'multiple' => \true,
            'ui' => \true,
            'ajax' => \true,
            // lazy load
            'return_format' => 'value',
        ]);
        foreach ($fields as $field) {
            $group->add_field($field);
        }
        return $group;
    }
    private function get_colors_field() : ACF\Field
    {
        $field = new ACF\Field(self::NAME . '_' . self::FIELD_COLOR);
        $field->set_attributes(['label' => \esc_html__('Color Theme', 'tribe-alerts'), 'instructions' => \esc_html__('Select a background color', 'tribe-alerts'), 'name' => self::FIELD_COLOR, 'type' => 'swatch', 'allow_null' => \false, 'default_value' => '', 'choices' => $this->color_options->get_acf_options()]);
        return $field;
    }
    private function get_allowed_post_types() : array
    {
        return \acf_get_post_types(['exclude' => [Alert::NAME, 'attachment']]);
    }
}
