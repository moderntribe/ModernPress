<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Log;

use Tribe\Alert_Scoped\Monolog\Handler\AbstractProcessingHandler;
use Tribe\Alert_Scoped\Monolog\Logger as MonoLogger;
use Tribe\Alert_Scoped\Psr\Log\LoggerInterface;
use Stringable;
/**
 * PSR Logger using MonoLogger.
 */
class Logger implements LoggerInterface
{
    /**
     * The MonoLogger Instance
     *
     * @var MonoLogger
     */
    protected $log;
    /**
     * Logger constructor.
     *
     * @param \Monolog\Logger $log
     */
    public function __construct(MonoLogger $log)
    {
        $this->log = $log;
    }
    /**
     * Adds additional push handlers
     *
     * @param AbstractProcessingHandler $handler
     *
     * @return LoggerInterface
     */
    public function add_push_handler(AbstractProcessingHandler $handler) : LoggerInterface
    {
        return $this->log->pushHandler($handler);
    }
    /**
     * System is unusable.
     *
     * @param string|Stringable $message
     * @param array             $context
     */
    public function emergency(string|Stringable $message, array $context = []) : void
    {
        $this->log->emergency($message, $context);
    }
    /**
     * Action must be taken immediately.
     *
     * @param string|Stringable $message
     * @param array             $context
     */
    public function alert(string|Stringable $message, array $context = []) : void
    {
        $this->log->alert($message, $context);
    }
    /**
     * Critical conditions.
     *
     * @param string|Stringable $message
     * @param array             $context
     */
    public function critical(string|Stringable $message, array $context = []) : void
    {
        $this->log->critical($message, $context);
    }
    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string|Stringable $message
     * @param array             $context
     */
    public function error(string|Stringable $message, array $context = []) : void
    {
        $this->log->error($message, $context);
    }
    /**
     * Exceptional occurrences that are not errors.
     *
     * @param string|Stringable $message
     * @param array             $context
     */
    public function warning(string|Stringable $message, array $context = []) : void
    {
        $this->log->warning($message, $context);
    }
    /**
     * Normal but significant events.
     *
     * @param string|Stringable $message
     * @param array             $context
     */
    public function notice(string|Stringable $message, array $context = []) : void
    {
        $this->log->notice($message, $context);
    }
    /**
     * Interesting events.
     *
     * @param string|Stringable $message
     * @param array             $context
     */
    public function info(string|Stringable $message, array $context = []) : void
    {
        $this->log->info($message, $context);
    }
    /**
     * Detailed debug information.
     *
     * @param string|Stringable $message
     * @param array             $context
     */
    public function debug(string|Stringable $message, array $context = []) : void
    {
        $this->log->debug($message, $context);
    }
    /**
     * Logs with an arbitrary level.
     *
     * @param mixed             $level
     * @param string|Stringable $message
     * @param array             $context
     *
     * @throws \Psr\Log\InvalidArgumentException
     */
    public function log($level, string|Stringable $message, array $context = []) : void
    {
        $this->log->log($level, $message, $context);
    }
}
