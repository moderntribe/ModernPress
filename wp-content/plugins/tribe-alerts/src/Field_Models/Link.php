<?php

declare (strict_types=1);
namespace Tribe\Alert\Field_Models;

use Tribe\Alert_Scoped\Spatie\DataTransferObject\FlexibleDataTransferObject;
/**
 * Represents an ACF Link field.
 */
class Link extends FlexibleDataTransferObject
{
    public string $url = '';
    public string $title = '';
    public string $target = '';
    public string $aria_label = '';
    public bool $add_aria_label = \false;
    public function __construct(array $parameters = [])
    {
        if (empty($parameters['title'])) {
            $parameters['title'] = \esc_html__('Find out more', 'tribe-alerts');
        }
        parent::__construct($parameters);
    }
}
