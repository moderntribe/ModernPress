<?php

namespace Tribe\Alert_Scoped\Tribe\Libs\P2P;

/**
 * Class Titles_Filter
 *
 * Adds the post type to the title displayed in the p2p meta
 * box to help distinguish between many posts of diverse types
 *
 * @package Tribe\Project\P2P
 */
class Titles_Filter
{
    /**
     * @var array Connection types that will have their titles filtered
     */
    private $connection_types = [];
    public function __construct(array $connection_types)
    {
        $this->connection_types = $connection_types;
    }
    /**
     * @param string $title
     * @param \WP_Post|\WP_User $object
     * @param \P2P_Directed_Connection_Type $connection_type
     *
     * @return string
     * @filter p2p_connected_title
     */
    public function filter_connection_name($title, $object, $connection_type)
    {
        if (empty($this->connection_types)) {
            return $title;
        }
        $p2p_id = $connection_type->name;
        if (!\in_array($p2p_id, $this->connection_types_to_label(), \true)) {
            return $title;
        }
        if ($object instanceof \WP_Post) {
            $post_type_label = $this->get_post_type_label($object->post_type);
        }
        if (!empty($post_type_label)) {
            $title = \sprintf('[%s] %s', $post_type_label, $title);
        }
        return $title;
    }
    /**
     * @param $title
     * @param \WP_Post|\WP_User $object
     *
     * @return string
     * @filter p2p_candidate_title
     */
    public function filter_candidate_name($title, $object)
    {
        if (empty($this->connection_types)) {
            return $title;
        }
        if (!$object instanceof \WP_Post) {
            return $title;
        }
        $post = $object;
        $connection_type = $this->get_connection_type();
        $connection_types = $this->connection_types_to_label();
        if (empty($connection_type) || !\in_array($connection_type, $connection_types)) {
            return $title;
        }
        $post_type_label = $this->get_post_type_label($post->post_type);
        if (empty($post_type_label)) {
            return $title;
        }
        return \sprintf('[%s] %s', $post_type_label, $title);
    }
    private function connection_types_to_label()
    {
        return \array_map(static function ($connection_type) {
            return $connection_type::NAME;
        }, $this->connection_types);
    }
    private function get_connection_type()
    {
        return isset($_REQUEST) && isset($_REQUEST['p2p_type']) ? $_REQUEST['p2p_type'] : \false;
    }
    private function get_post_type_label($post_type)
    {
        $post_type_object = \get_post_type_object($post_type);
        if ($post_type_object) {
            return $post_type_object->labels->singular_name;
        }
        return '';
    }
}
