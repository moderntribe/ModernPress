<?php

namespace Tribe\Alert_Scoped\Spatie\DataTransferObject;

abstract class FlexibleDataTransferObject extends DataTransferObject
{
    protected bool $ignoreMissing = \true;
}
