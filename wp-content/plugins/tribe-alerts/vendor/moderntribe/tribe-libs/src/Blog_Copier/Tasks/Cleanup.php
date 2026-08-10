<?php

namespace Tribe\Alert_Scoped\Tribe\Libs\Blog_Copier\Tasks;

use Tribe\Alert_Scoped\Tribe\Libs\Blog_Copier\Copy_Manager;
use Tribe\Alert_Scoped\Tribe\Libs\Queues\Contracts\Task;
class Cleanup implements Task
{
    public function handle(array $args) : bool
    {
        $post_id = empty($args['post_id']) ? 0 : \absint($args['post_id']);
        \wp_delete_post($post_id, \true);
        \do_action(Copy_Manager::TASK_COMPLETE_ACTION, static::class, $args);
        return \true;
    }
}
