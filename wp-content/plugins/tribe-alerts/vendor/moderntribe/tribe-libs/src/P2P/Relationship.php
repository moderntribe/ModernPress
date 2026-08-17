<?php

namespace Tribe\Alert_Scoped\Tribe\Libs\P2P;

abstract class Relationship
{
    const NAME = '';
    // override this in a child class
    /**
     * @var array Object types on the from side of the relationship
     */
    protected $from = [];
    /**
     * @var array Object types on the to side of the relationship
     */
    protected $to = [];
    public function __construct(array $from = [], array $to = [])
    {
        if (!empty($from)) {
            $this->from = $from;
        }
        if (!empty($to)) {
            $this->to = $to;
        }
        $this->from = $this->normalize_side($this->from);
        $this->to = $this->normalize_side($this->to);
    }
    public function hook()
    {
        \add_action('p2p_init', [$this, 'register'], 10, 0);
    }
    /**
     * @return void
     * @action p2p_init
     */
    public function register()
    {
        \p2p_register_connection_type($this->get_all_args());
    }
    private function get_all_args()
    {
        return \wp_parse_args($this->get_args(), ['id' => static::NAME, 'from' => $this->from, 'to' => $this->to]);
    }
    public function from()
    {
        return $this->from;
    }
    public function to()
    {
        return $this->to;
    }
    protected abstract function get_args();
    protected function normalize_side($side)
    {
        if (\in_array('user', $side, \true)) {
            return 'user';
        }
        return \array_filter($side, [$this, 'is_registered_post_type']);
    }
    /**
     * Registering a p2p connection type with an unregistered post type
     * will throw an error.
     *
     * @param string $post_type
     *
     * @return bool Whether the post type is registered
     */
    protected function is_registered_post_type($post_type)
    {
        return \get_post_type_object($post_type) !== null;
    }
}
