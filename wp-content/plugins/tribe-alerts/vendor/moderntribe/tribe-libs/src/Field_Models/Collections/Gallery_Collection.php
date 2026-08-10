<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Field_Models\Collections;

use Tribe\Alert_Scoped\Tribe\Libs\Field_Models\DTO\Data_Transfer_Object_Collection;
use Tribe\Alert_Scoped\Tribe\Libs\Field_Models\Models\Image;
class Gallery_Collection extends Data_Transfer_Object_Collection
{
    public static function create(array $attachments) : Gallery_Collection
    {
        return new static(Image::arrayOf($attachments));
    }
    public function current() : ?Image
    {
        return parent::current();
    }
    public function offsetGet($offset) : ?Image
    {
        return parent::offsetGet($offset);
    }
}
