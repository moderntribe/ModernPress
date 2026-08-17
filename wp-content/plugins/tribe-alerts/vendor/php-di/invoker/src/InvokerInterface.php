<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Invoker;

use Tribe\Alert_Scoped\Invoker\Exception\InvocationException;
use Tribe\Alert_Scoped\Invoker\Exception\NotCallableException;
use Tribe\Alert_Scoped\Invoker\Exception\NotEnoughParametersException;
/**
 * Invoke a callable.
 */
interface InvokerInterface
{
    /**
     * Call the given function using the given parameters.
     *
     * @param callable|array|string $callable Function to call.
     * @param array $parameters Parameters to use.
     * @return mixed Result of the function.
     * @throws InvocationException Base exception class for all the sub-exceptions below.
     * @throws NotCallableException
     * @throws NotEnoughParametersException
     */
    public function call($callable, array $parameters = []);
}
