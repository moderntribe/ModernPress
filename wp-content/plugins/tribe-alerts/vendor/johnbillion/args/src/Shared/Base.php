<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Args\Shared;

use Tribe\Alert_Scoped\Args\Arrayable\Arrayable;
abstract class Base implements Arrayable
{
    public const ORDER_ASC = 'ASC';
    public const ORDER_DESC = 'DESC';
    use \Tribe\Alert_Scoped\Args\Arrayable\ProvidesFromArray;
    use \Tribe\Alert_Scoped\Args\Arrayable\ProvidesToArray;
    public final function __construct()
    {
        if ($this instanceof \Tribe\Alert_Scoped\Args\DateQuery\WithArgs) {
            $this->setDateQuery(new \Tribe\Alert_Scoped\Args\DateQuery\Query());
        }
        if ($this instanceof \Tribe\Alert_Scoped\Args\MetaQuery\WithArgs) {
            $this->setMetaQuery(new \Tribe\Alert_Scoped\Args\MetaQuery\Query());
        }
        if ($this instanceof \Tribe\Alert_Scoped\Args\TaxQuery\WithArgs) {
            $this->setTaxQuery(new \Tribe\Alert_Scoped\Args\TaxQuery\Query());
        }
    }
}
