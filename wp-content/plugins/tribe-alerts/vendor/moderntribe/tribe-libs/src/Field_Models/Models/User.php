<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Field_Models\Models;

use Tribe\Alert_Scoped\Tribe\Libs\Field_Models\Field_Model;
class User extends Field_Model
{
    public int $ID = 0;
    public string $user_firstname = '';
    public string $user_lastname = '';
    public string $nickname = '';
    public string $user_nicename = '';
    public string $display_name = '';
    public string $user_email = '';
    public string $user_url = '';
    public string $user_registered = '';
    public string $user_description = '';
    public string $user_avatar = '';
}
