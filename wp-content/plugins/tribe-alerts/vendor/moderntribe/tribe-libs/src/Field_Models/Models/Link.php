<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Field_Models\Models;

use Tribe\Alert_Scoped\Tribe\Libs\Field_Models\Field_Model;
class Link extends Field_Model
{
    public string $title = '';
    public string $url = '';
    public string $target = '_self';
}
