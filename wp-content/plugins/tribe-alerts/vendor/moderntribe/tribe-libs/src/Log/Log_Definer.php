<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Log;

use Tribe\Alert_Scoped\DI;
use Tribe\Alert_Scoped\MHCG\Monolog\Handler\WPCLIHandler;
use Tribe\Alert_Scoped\Monolog\Handler\StreamHandler;
use Tribe\Alert_Scoped\Monolog\Logger as MonoLogger;
use Tribe\Alert_Scoped\Psr\Container\ContainerInterface;
use Tribe\Alert_Scoped\Psr\Log\LoggerInterface;
use Tribe\Alert_Scoped\Psr\Log\LogLevel;
use Tribe\Alert_Scoped\Tribe\Libs\Container\Definer_Interface;
class Log_Definer implements Definer_Interface
{
    public const HANDLERS = 'libs.log.handlers';
    public const LOG_LEVEL = 'libs.log.level';
    public function define() : array
    {
        return [
            StreamHandler::class => static function (ContainerInterface $c) {
                $path = \WP_CONTENT_DIR . '/square-one-' . \date('Y-m-d') . '.log';
                $path = \apply_filters('tribe/log/path', $path);
                return new StreamHandler($path, $c->get(self::LOG_LEVEL));
            },
            // Define the default MonoLogger handlers
            self::HANDLERS => DI\add([DI\get(StreamHandler::class)]),
            self::LOG_LEVEL => static function () {
                return \defined('TRIBE_LOG_LEVEL') ? TRIBE_LOG_LEVEL : LogLevel::DEBUG;
            },
            MonoLogger::class => static function (ContainerInterface $c) {
                $log_channel = \apply_filters('tribe/log/channel', 'square-one');
                return new MonoLogger($log_channel, $c->get(self::HANDLERS));
            },
            WPCLIHandler::class => DI\autowire()->constructorParameter('level', DI\get(self::LOG_LEVEL)),
            LoggerInterface::class => static function (ContainerInterface $c) {
                $logger = new Logger($c->get(MonoLogger::class));
                // Add a WP CLI MonoLogger handler
                if (\defined('WP_CLI') && WP_CLI && (\defined('TRIBE_LOG_CLI') && TRIBE_LOG_CLI)) {
                    $cli_handler = $c->get(WPCLIHandler::class);
                    $logger->add_push_handler($cli_handler);
                }
                return $logger;
            },
        ];
    }
}
