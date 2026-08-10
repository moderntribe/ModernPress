<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\DI;

use Tribe\Alert_Scoped\Psr\Container\ContainerExceptionInterface;
/**
 * Exception for the Container.
 */
class DependencyException extends \Exception implements ContainerExceptionInterface
{
}
