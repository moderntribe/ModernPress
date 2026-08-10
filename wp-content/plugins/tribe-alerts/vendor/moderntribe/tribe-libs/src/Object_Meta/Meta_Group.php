<?php

namespace Tribe\Alert_Scoped\Tribe\Libs\Object_Meta;

/**
 * Class Meta_Group
 *
 * A container for one or more object meta fields.
 *
 * It is the responsibility of instances of this class to
 *  - register meta boxes/fields with WP (probably via a lib like ACF or CMB2)
 *  - return a list of keys for which this group is willing to handle finding a value
 *  - return the appropriate value when one of said keys is requested
 *
 * Examples:
 *  new Instance_Class([
 *  	'post_types' => ['post', 'page'],
 *  ]);
 *
 *  new Instance_Class([
 * 		'taxonomies' => ['post_tag', 'category'],
 *  ]);
 *
 *  new Instance_Class([
 * 		'settings_pages' => ['options-general-php-general-settings'],
 *  ]);
 *
 *  new Instance_Class([
 * 		'users' => true,
 *  ]);
 *
 *  new Instance_Class([
 * 		'nav_menus' => ['location/menu_location_slug'],
 *  ]);
 *
 *  // include a menu location
 *  new Instance_Class([
 * 		'nav_menu_items' => ['location/menu_location_slug'],
 *  ]);
 *
 *  // include a specific menu based on term_id (e.g. menu_id)
 *  new Instance_Class([
 * 		'nav_menu_items' => [ 4 ],
 *  ]);
 *
 * // include an ACF block
 * new Instance_Class([
 *     'block' => [ 'block-name' ],
 * ]);
 */
abstract class Meta_Group
{
    const NAME = '';
    protected $post_types = [];
    protected $object_types = [];
    /**
     * Meta_Group constructor.
     *
     * @param array $object_types The object types the meta group applies to
     */
    public function __construct(array $object_types)
    {
        // Allow backwards compatibility with the former method of assigning post types to meta groups.
        $types = ['post_types', 'taxonomies', 'settings_pages', 'users', 'nav_menus', 'nav_menu_items', 'widget', 'block'];
        if (empty(\array_intersect($types, \array_keys($object_types)))) {
            $this->post_types = $object_types;
            $object_types = ['post_types' => $object_types];
        }
        $this->object_types = $object_types;
    }
    /**
     * @return array Return the post types for this meta group. This method exists purely to allow backwards compatibility with
     *               older versions of the Post_Meta class.
     *
     * @deprecated Object meta should be registered using an array of key=>value pairs for object types. E.g. [ 'post_types' => [ 'page' ], 'taxonomies' => ['category'] ]
     */
    public function get_post_types()
    {
        return $this->object_types['post_types'];
    }
    /**
     * @return array The post types this meta group applies to
     */
    public function get_object_types()
    {
        return $this->object_types;
    }
    /**
     * @return array The meta keys that this field will handle.
     *               While these will probably directly correspond
     *               to meta keys in the database, there is no
     *               guaranteed, as the key may correspond to
     *               a computed/aggregate value.
     */
    public abstract function get_keys();
    /**
     * @param int    $object_id
     * @param string $key
     *
     * @return mixed The value for the given key
     */
    public abstract function get_value($object_id, $key);
}
