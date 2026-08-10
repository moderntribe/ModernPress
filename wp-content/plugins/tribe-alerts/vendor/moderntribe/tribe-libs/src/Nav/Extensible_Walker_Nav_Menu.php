<?php

namespace Tribe\Alert_Scoped\Tribe\Libs\Nav;

use Walker_Nav_Menu;
/**
 * Class Extensible_Walker_Nav_Menu
 *
 * Just like \Walker_Nav_Menu, but with more hooks
 *
 * @package Tribe\Project\Nav
 */
class Extensible_Walker_Nav_Menu extends Walker_Nav_Menu
{
    /**
     * Starts the list before the elements are added.
     *
     * @since 3.0.0
     *
     * @see   Walker::start_lvl()
     *
     * @param string $output Passed by reference. Used to append additional content.
     * @param int    $depth  Depth of menu item. Used for padding.
     * @param array  $args   An array of wp_nav_menu() arguments.
     */
    public function start_lvl(&$output, $depth = 0, $args = [])
    {
        $indent = \str_repeat("\t", $depth);
        $tag = \apply_filters('nav_menu_start_level_tag', 'ul', $depth, $args);
        $classes = \apply_filters('nav_menu_start_level_classes', ['sub-menu'], $depth, $args);
        $classes = \implode(' ', \array_filter(\array_map('sanitize_html_class', $classes)));
        $output .= "\n{$indent}<{$tag} class=\"{$classes}\">\n";
    }
    /**
     * Ends the list of after the elements are added.
     *
     * @since 3.0.0
     *
     * @see   Walker::end_lvl()
     *
     * @param string $output Passed by reference. Used to append additional content.
     * @param int    $depth  Depth of menu item. Used for padding.
     * @param array  $args   An array of wp_nav_menu() arguments.
     */
    public function end_lvl(&$output, $depth = 0, $args = [])
    {
        $tag = \apply_filters('nav_menu_end_level_tag', 'ul', $depth, $args);
        $indent = \str_repeat("\t", $depth);
        $output .= "{$indent}</{$tag}>\n";
    }
    /**
     * Starts the element output.
     *
     * @param  string          $output  Passed by reference. Used to append additional content.
     * @param  \WP_Post        $data_object    Menu item data object.
     * @param  int             $depth   Depth of menu item. Used for padding.
     * @param  \stdClass|null  $args    An array of wp_nav_menu() arguments.
     * @param  int             $id      Current item ID.
     *
     * @see   Walker::start_el()
     *
     * @since 3.0.0
     * @since 4.4.0 The {@see 'nav_menu_item_args'} filter was added.
     *
     */
    public function start_el(&$output, $data_object, $depth = 0, $args = null, $id = 0)
    {
        $item = $data_object;
        $indent = $depth ? \str_repeat("\t", $depth) : '';
        $classes = empty($item->classes) ? [] : (array) $item->classes;
        $classes[] = 'menu-item-' . $item->ID;
        /**
         * Filters the arguments for a single nav menu item.
         *
         * @param  \stdClass  $args       An object of wp_nav_menu() arguments.
         * @param  \WP_Post   $menu_item  Menu item data object.
         * @param  int        $depth      Depth of menu item. Used for padding.
         *
         * @since 4.4.0
         */
        $args = \apply_filters('nav_menu_item_args', $args, $item, $depth);
        /**
         * Filters the CSS class(es) applied to a menu item's list item element.
         *
         * @since 3.0.0
         * @since 4.1.0 The `$depth` parameter was added.
         *
         * @param array  $classes The CSS classes that are applied to the menu item's `<li>` element.
         * @param object $item    The current menu item.
         * @param array  $args    An array of wp_nav_menu() arguments.
         * @param int    $depth   Depth of menu item. Used for padding.
         */
        $class_names = \implode(' ', \apply_filters('nav_menu_css_class', \array_filter(\array_map('sanitize_html_class', $classes)), $item, $args, $depth));
        $class_names = $class_names ? ' class="' . \esc_attr($class_names) . '"' : '';
        $item_tag = \apply_filters('nav_menu_item_tag', 'li', $item, $args, $depth);
        /**
         * Filters the ID applied to a menu item's list item element.
         *
         * @param  string     $menu_id  The ID that is applied to the menu item's `<li>` element.
         * @param  object     $item     The current menu item.
         * @param  \stdClass  $args     An object of wp_nav_menu() arguments.
         * @param  int        $depth    Depth of menu item. Used for padding.
         *
         * @since 3.0.1
         * @since 4.1.0 The `$depth` parameter was added.
         *
         */
        $id = \apply_filters('nav_menu_item_id', 'menu-item-' . $item->ID, $item, $args, $depth);
        $id = $id ? ' id="' . \esc_attr($id) . '"' : '';
        $output .= $indent . '<' . $item_tag . $id . $class_names . '>';
        $atts = [];
        $atts['title'] = !empty($item->attr_title) ? $item->attr_title : '';
        $atts['target'] = !empty($item->target) ? $item->target : '';
        $atts['rel'] = !empty($item->xfn) ? $item->xfn : '';
        $atts['href'] = !empty($item->url) ? $item->url : '';
        /**
         * Filters the HTML attributes applied to a menu item's anchor element.
         *
         * @param  array      $atts        {
         *                                 The HTML attributes applied to the menu item's `<a>` element, empty strings are ignored.
         *
         * @type string       $title       Title attribute.
         * @type string       $target      Target attribute.
         * @type string       $rel         The rel attribute.
         * @type string       $href        The href attribute.
         *                                 }
         *
         * @param  object     $item        The current menu item.
         * @param  \stdClass  $args        An object of wp_nav_menu() arguments.
         * @param  int        $depth       Depth of menu item. Used for padding.
         *
         * @since 3.6.0
         * @since 4.1.0 The `$depth` parameter was added.
         *
         */
        $atts = \apply_filters('nav_menu_link_attributes', $atts, $item, $args, $depth);
        $attributes = '';
        foreach ($atts as $attr => $value) {
            if (!empty($value)) {
                $value = 'href' === $attr ? \esc_url($value) : \esc_attr($value);
                $attributes .= ' ' . $attr . '="' . $value . '"';
            }
        }
        /** This filter is documented in wp-includes/post-template.php */
        // @phpstan-ignore-next-line This is exactly how it is in core.
        $title = \apply_filters('the_title', $item->title, $item->ID);
        /**
         * Filters a menu item's title.
         *
         * @param  string     $title  The menu item's title.
         * @param  object     $item   The current menu item.
         * @param  \stdClass  $args   An object of wp_nav_menu() arguments.
         * @param  int        $depth  Depth of menu item. Used for padding.
         *
         * @since 4.4.0
         */
        $title = \apply_filters('nav_menu_item_title', $title, $item, $args, $depth);
        $link_tag = \apply_filters('nav_menu_link_tag', 'a', $item, $args, $depth);
        $item_output = $args->before;
        $item_output .= "<{$link_tag}" . $attributes . '>';
        $item_output .= $args->link_before . $title . $args->link_after;
        $item_output .= "</{$link_tag}>";
        $item_output .= $args->after;
        /**
         * Filters a menu item's starting output.
         *
         * The menu item's starting output only includes `$args->before`, the opening `<a>`,
         * the menu item's title, the closing `</a>`, and `$args->after`. Currently, there is
         * no filter for modifying the opening and closing `<li>` for a menu item.
         *
         * @param  string          $item_output  The menu item's starting HTML output.
         * @param  object          $item         Menu item data object.
         * @param  int             $depth        Depth of menu item. Used for padding.
         * @param  \stdClass|null  $args         An array of wp_nav_menu() arguments.
         *
         * @since 3.0.0
         */
        $output .= \apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
    }
    /**
     * Ends the element output, if needed.
     *
     * @param  string          $output       Passed by reference. Used to append additional content.
     * @param  object          $data_object  Page data object. Not used.
     * @param  int             $depth        Depth of page. Not Used.
     * @param  \stdClass|null  $args         An object of wp_nav_menu() arguments.
     *
     * @see   Walker::end_el()
     *
     * @since 3.0.0
     *
     */
    public function end_el(&$output, $data_object, $depth = 0, $args = null)
    {
        $item_tag = \apply_filters('nav_menu_item_tag', 'li', $data_object, $args, $depth);
        $output .= "</{$item_tag}\n";
    }
}
