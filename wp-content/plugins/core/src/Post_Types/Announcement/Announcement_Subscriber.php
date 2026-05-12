<?php declare(strict_types=1);

namespace Tribe\Plugin\Post_Types\Announcement;

use Tribe\Plugin\Post_Types\Post_Type_Subscriber;

class Announcement_Subscriber extends Post_Type_Subscriber {

	protected string $config_class = Config::class;

}
