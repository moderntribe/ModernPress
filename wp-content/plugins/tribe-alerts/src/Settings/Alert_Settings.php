<?php

declare (strict_types=1);
namespace Tribe\Alert\Settings;

use Tribe\Alert\Post_Types\Alert\Alert;
use Tribe\Alert_Scoped\Tribe\Libs\ACF\ACF_Settings;
class Alert_Settings extends ACF_Settings
{
    public function get_title() : string
    {
        return \__('Settings', 'tribe-alerts');
    }
    public function get_capability() : string
    {
        return 'activate_plugins';
    }
    public function get_parent_slug() : string
    {
        return \sprintf('edit.php?post_type=%s', Alert::NAME);
    }
}
