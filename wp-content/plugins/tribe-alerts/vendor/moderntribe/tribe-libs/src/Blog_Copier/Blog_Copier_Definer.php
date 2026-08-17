<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Blog_Copier;

use Tribe\Alert_Scoped\DI;
use Tribe\Alert_Scoped\Tribe\Libs\Blog_Copier\Strategies\File_Copy_Strategy;
use Tribe\Alert_Scoped\Tribe\Libs\Blog_Copier\Strategies\Shell_File_Copy;
use Tribe\Alert_Scoped\Tribe\Libs\Blog_Copier\Tasks\Cleanup;
use Tribe\Alert_Scoped\Tribe\Libs\Blog_Copier\Tasks\Copy_Files;
use Tribe\Alert_Scoped\Tribe\Libs\Blog_Copier\Tasks\Create_Blog;
use Tribe\Alert_Scoped\Tribe\Libs\Blog_Copier\Tasks\Mark_Complete;
use Tribe\Alert_Scoped\Tribe\Libs\Blog_Copier\Tasks\Replace_Guids;
use Tribe\Alert_Scoped\Tribe\Libs\Blog_Copier\Tasks\Replace_Options;
use Tribe\Alert_Scoped\Tribe\Libs\Blog_Copier\Tasks\Replace_Tables;
use Tribe\Alert_Scoped\Tribe\Libs\Blog_Copier\Tasks\Replace_Urls;
use Tribe\Alert_Scoped\Tribe\Libs\Blog_Copier\Tasks\Send_Notifications;
use Tribe\Alert_Scoped\Tribe\Libs\Container\Definer_Interface;
use Tribe\Alert_Scoped\Tribe\Libs\Queues\Contracts\Queue;
class Blog_Copier_Definer implements Definer_Interface
{
    public const TASK_CHAIN = 'libs.blog_copier.task_chain';
    public function define() : array
    {
        return [Copy_Manager::class => DI\autowire()->constructor(DI\get(Queue::class), DI\get(self::TASK_CHAIN)), self::TASK_CHAIN => function () {
            return new Task_Chain([Create_Blog::class, Replace_Tables::class, Replace_Options::class, Replace_Guids::class, Copy_Files::class, Replace_Urls::class, Mark_Complete::class, Send_Notifications::class, Cleanup::class]);
        }, File_Copy_Strategy::class => DI\autowire(Shell_File_Copy::class)];
    }
}
