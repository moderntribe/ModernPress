<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Blog_Copier;

use Tribe\Alert_Scoped\Tribe\Libs\Blog_Copier\Strategies\File_Copy_Strategy;
use Tribe\Alert_Scoped\Tribe\Libs\Container\Abstract_Subscriber;
class Blog_Copier_Subscriber extends Abstract_Subscriber
{
    public function register() : void
    {
        $this->post_type();
        $this->admin();
        $this->manager();
        $this->file_copy();
    }
    protected function post_type() : void
    {
        \add_action('init', function () {
            Copy_Manager::register_post_type();
        });
    }
    protected function admin()
    {
        \add_action('network_admin_menu', function () {
            $this->container->get(Network_Admin_Screen::class)->register_screen();
        }, 10, 0);
        \add_action('network_admin_edit_' . Network_Admin_Screen::NAME, function () {
            $this->container->get(Network_Admin_Screen::class)->handle_submission();
        }, 10, 0);
    }
    protected function manager()
    {
        \add_action('tribe/project/copy-blog/copy', function (Copy_Configuration $configuration) {
            $this->container->get(Copy_Manager::class)->initialize($configuration);
        }, 10, 1);
        \add_action(Copy_Manager::TASK_COMPLETE_ACTION, function ($completed_task, $args) {
            $this->container->get(Copy_Manager::class)->schedule_next_step($completed_task, $args);
        }, 10, 2);
    }
    protected function file_copy()
    {
        \add_action('tribe/project/copy-blog/copy-files', function ($src, $dest) {
            $this->container->get(File_Copy_Strategy::class)->handle_copy($src, $dest);
        }, 10, 2);
    }
}
