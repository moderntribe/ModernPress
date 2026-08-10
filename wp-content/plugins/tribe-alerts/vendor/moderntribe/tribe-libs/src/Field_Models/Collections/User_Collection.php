<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Field_Models\Collections;

use Tribe\Alert_Scoped\Tribe\Libs\Field_Models\DTO\Data_Transfer_Object_Collection;
use Tribe\Alert_Scoped\Tribe\Libs\Field_Models\Models\User;
class User_Collection extends Data_Transfer_Object_Collection
{
    public static function create(array $users) : User_Collection
    {
        return new static(User::arrayOf($users));
    }
    public function current() : ?User
    {
        return parent::current();
    }
    public function offsetGet($offset) : ?User
    {
        return parent::offsetGet($offset);
    }
}
