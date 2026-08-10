<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Field_Models\Models;

use Tribe\Alert_Scoped\Tribe\Libs\Field_Models\Field_Model;
/**
 * Custom call to action model.
 */
class Cta extends Field_Model
{
    /**
     * @var \Tribe\Libs\Field_Models\Models\Link
     */
    public Link $link;
    public bool $add_aria_label = \false;
    public string $aria_label = '';
}
