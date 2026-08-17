<?php

declare (strict_types=1);
namespace Tribe\Alert\Components\Alert;

use Closure;
interface Rule
{
    /**
     * Determine if we should display an alert. A rule can return
     * a result or pass itself onto the next rule for processing.
     *
     * @param bool     $display Whether an alert will display.
     * @param \Closure $next    The next rule in the pipeline.
     * @param mixed[]  $rules   The Alert Meta ACF Rules Group.
     */
    public function handle(bool $display, Closure $next, array $rules) : bool;
}
