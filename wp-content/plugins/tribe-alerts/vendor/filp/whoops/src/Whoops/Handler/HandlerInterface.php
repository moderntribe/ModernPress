<?php

/**
 * Whoops - php errors for cool kids
 * @author Filipe Dobreira <http://github.com/filp>
 */
namespace Tribe\Alert_Scoped\Whoops\Handler;

use Tribe\Alert_Scoped\Whoops\Inspector\InspectorInterface;
use Tribe\Alert_Scoped\Whoops\RunInterface;
interface HandlerInterface
{
    /**
     * @return int|null A handler may return nothing, or a Handler::HANDLE_* constant
     */
    public function handle();
    /**
     * @param  RunInterface  $run
     * @return void
     */
    public function setRun(RunInterface $run);
    /**
     * @param  \Throwable $exception
     * @return void
     */
    public function setException($exception);
    /**
     * @param  InspectorInterface $inspector
     * @return void
     */
    public function setInspector(InspectorInterface $inspector);
}
