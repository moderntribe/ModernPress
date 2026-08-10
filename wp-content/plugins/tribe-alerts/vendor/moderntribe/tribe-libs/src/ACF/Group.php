<?php

namespace Tribe\Alert_Scoped\Tribe\Libs\ACF;

class Group extends ACF_Configuration implements ACF_Aggregate
{
    protected $key_prefix = 'group';
    /** @var Field[] */
    protected $fields = [];
    protected $post_types = [];
    protected $taxonomies = [];
    protected $settings_pages = [];
    protected $nav_menus = [];
    protected $nav_menu_items = [];
    protected $widgets = [];
    protected $blocks = [];
    protected $users = \false;
    /**
     * Group constructor.
     *
     * @param string     $key
     * @param array|bool $object_types
     */
    public function __construct($key, $object_types = \false)
    {
        parent::__construct($key);
        if ($object_types) {
            if (isset($object_types['post_types'])) {
                $this->set_post_types($object_types['post_types']);
            }
            if (isset($object_types['taxonomies'])) {
                $this->set_taxonomies($object_types['taxonomies']);
            }
            if (isset($object_types['settings_pages'])) {
                $this->set_settings_pages($object_types['settings_pages']);
            }
            if (isset($object_types['users'])) {
                $this->toggle_users($object_types['users']);
            }
            if (isset($object_types['nav_menus'])) {
                $this->set_nav_menus($object_types['nav_menus']);
            }
            if (isset($object_types['nav_menu_items'])) {
                $this->set_nav_menu_items($object_types['nav_menu_items']);
            }
            if (isset($object_types['widget'])) {
                $this->set_widgets($object_types['widget']);
            }
            if (isset($object_types['block'])) {
                $this->set_blocks($object_types['block']);
            }
        }
    }
    /**
     * Add a field to the group.
     *
     * @param Field $field
     *
     * @return $this
     */
    public function add_field(Field $field) : Group
    {
        $this->fields[] = $field;
        return $this;
    }
    /**
     * Get the attributes for this group.
     *
     * @return array
     */
    public function get_attributes()
    {
        $attributes = $this->attributes;
        $attributes['fields'] = $this->fields ? \array_merge(...\array_map(function (Field $f) {
            return $f->get_attributes();
        }, $this->fields)) : [];
        $attributes = $this->set_location_restrictions($attributes);
        return $attributes;
    }
    /**
     * Assign the location restrictions for this group.
     *
     * @param array $attributes
     *
     * @return array
     */
    protected function set_location_restrictions($attributes)
    {
        foreach ($this->post_types as $post_type) {
            if ($post_type === 'attachment') {
                $attributes['location'][] = [['param' => 'attachment', 'operator' => '==', 'value' => 'all']];
            } else {
                $attributes['location'][] = [['param' => 'post_type', 'operator' => '==', 'value' => $post_type]];
            }
        }
        foreach ($this->taxonomies as $taxonomy) {
            $attributes['location'][] = [['param' => 'taxonomy', 'operator' => '==', 'value' => $taxonomy]];
        }
        foreach ($this->settings_pages as $page) {
            $attributes['location'][] = [['param' => 'options_page', 'operator' => '==', 'value' => $page]];
        }
        if ($this->users) {
            $attributes['location'][] = [['param' => 'user_form', 'operator' => '==', 'value' => 'edit']];
        }
        foreach ($this->nav_menus as $nav_menu) {
            $attributes['location'][] = [['param' => 'nav_menu', 'operator' => '==', 'value' => $nav_menu]];
        }
        foreach ($this->nav_menu_items as $nav_menu_item) {
            $attributes['location'][] = [['param' => 'nav_menu_item', 'operator' => '==', 'value' => $nav_menu_item]];
        }
        foreach ($this->widgets as $widget) {
            $attributes['location'][] = [['param' => 'widget', 'operator' => '==', 'value' => $widget]];
        }
        foreach ($this->blocks as $block) {
            $attributes['location'][] = [['param' => 'block', 'operator' => '==', 'value' => "acf/{$block}"]];
        }
        return $attributes;
    }
    /**
     * Toggle whether to show on the user Add/Edit forms.
     *
     * @param bool $enable
     */
    public function toggle_users(bool $enable)
    {
        $this->users = $enable;
    }
    /**
     * Set the taxonomies for this group.
     *
     * @param array $taxonomies
     */
    public function set_taxonomies(array $taxonomies)
    {
        $this->taxonomies = $taxonomies;
    }
    /**
     * Set the post types in which this group will be available
     *
     * @param array $post_types
     *
     * @return void
     */
    public function set_post_types(array $post_types)
    {
        $this->post_types = $post_types;
    }
    public function set_settings_pages($pages)
    {
        $this->settings_pages = $pages;
    }
    public function set_nav_menus(array $nav_menus)
    {
        $this->nav_menus = $nav_menus;
    }
    public function set_nav_menu_items(array $nav_menu_items)
    {
        $this->nav_menu_items = $nav_menu_items;
    }
    public function set_widgets(array $widget)
    {
        $this->widgets = $widget;
    }
    public function set_blocks(array $blocks)
    {
        $this->blocks = $blocks;
    }
}
