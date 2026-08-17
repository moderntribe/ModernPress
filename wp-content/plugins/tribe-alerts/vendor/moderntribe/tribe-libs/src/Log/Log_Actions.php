<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Log;

use Tribe\Alert_Scoped\Psr\Log\LoggerInterface;
/**
 * Log data using WordPress actions.
 *
 * @example do_action( Log_Actions::WARNING, 'Something is wrong!', [ 'additional data' ] );
 */
class Log_Actions
{
    public const EMERGENCY = 'tribe/log/emergency';
    public const ALERT = 'tribe/log/alert';
    public const CRITICAL = 'tribe/log/critical';
    public const ERROR = 'tribe/log/error';
    public const WARNING = 'tribe/log/warning';
    public const NOTICE = 'tribe/log/notice';
    public const INFO = 'tribe/log/info';
    public const DEBUG = 'tribe/log/debug';
    /**
     * The logger instance
     *
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * Log_Actions constructor.
     *
     * @param  LoggerInterface  $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    /**
     * Initialize the actions
     *
     * @action init
     */
    public function init()
    {
        \add_action(self::EMERGENCY, [$this->logger, 'emergency'], 10, 2);
        \add_action(self::ALERT, [$this->logger, 'alert'], 10, 2);
        \add_action(self::CRITICAL, [$this->logger, 'critical'], 10, 2);
        \add_action(self::ERROR, [$this->logger, 'error'], 10, 2);
        \add_action(self::WARNING, [$this->logger, 'warning'], 10, 2);
        \add_action(self::NOTICE, [$this->logger, 'notice'], 10, 2);
        \add_action(self::INFO, [$this->logger, 'info'], 10, 2);
        \add_action(self::DEBUG, [$this->logger, 'debug'], 10, 2);
    }
}
