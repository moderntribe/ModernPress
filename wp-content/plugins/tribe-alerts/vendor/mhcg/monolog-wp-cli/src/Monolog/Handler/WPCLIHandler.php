<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\MHCG\Monolog\Handler;

use Tribe\Alert_Scoped\Monolog\Logger;
use Tribe\Alert_Scoped\Monolog\Formatter\LineFormatter;
use Tribe\Alert_Scoped\Monolog\Formatter\FormatterInterface;
use Tribe\Alert_Scoped\Monolog\Handler\AbstractProcessingHandler;
use Tribe\Alert_Scoped\WP_CLI;
/**
 * Handler for Monolog that uses WP-CLI methods to for logging.
 *
 * @since 1.0.0
 * @link https://github.com/markheydon/monolog-wp-cli
 * @author Mark Heydon <contact@mhcg.co.uk>
 * @license MIT
 * @package MHCG\Monolog\Handler
 */
class WPCLIHandler extends AbstractProcessingHandler
{
    /** @var string Format used when WP_DEBUG disabled */
    public const WP_CLI_FORMAT_STANDARD = "%message%";
    /** @var string Format used when WP_DEBUG enabled */
    public const WP_CLI_FORMAT_VERBOSE = "%message% %context% %extra%";
    /** @var bool Use verbose style log message format */
    private $verbose = \false;
    /** @var array Logger map to use for mapping Logger methods to WP-CLI methods */
    private $loggerMap;
    /**
     * WPCLIHandler constructor.
     *
     * @param int $level The minimum logging level at which this handler will be triggered
     * @param bool $bubble Whether the messages that are handled can bubble up the stack or not
     * @param bool $verbose Will use this or WP_DEBUG to include extra information in logging messages
     * @param array|null $loggerMap Optional logger map overrides merged over the defaults
     */
    public function __construct($level = Logger::WARNING, $bubble = \true, $verbose = \false, ?array $loggerMap = null)
    {
        $isInCLI = \defined('WP_CLI') && WP_CLI;
        if (!$isInCLI) {
            throw new \RuntimeException('');
        }
        parent::__construct($level, $bubble);
        $verbose = (\defined('WP_DEBUG') ? \WP_DEBUG : \false) || $verbose;
        $this->verbose = $verbose;
        if ($loggerMap !== null) {
            $this->loggerMap = self::mergeLoggerMapOverrides($loggerMap);
            self::validateAllLoggerMapEntries($this->loggerMap);
        }
    }
    /**
     * Merge user logger map overrides over defaults at per-level key depth.
     *
     * @param array $loggerMap Override entries keyed by Monolog level.
     *
     * @return array
     */
    private static function mergeLoggerMapOverrides(array $loggerMap) : array
    {
        $defaultMap = self::getDefaultLoggerMap();
        foreach ($loggerMap as $level => $entry) {
            $level = (int) $level;
            if (!isset($defaultMap[$level]) || !\is_array($defaultMap[$level]) || !\is_array($entry)) {
                $defaultMap[$level] = $entry;
                continue;
            }
            $defaultMap[$level] = \array_replace($defaultMap[$level], $entry);
        }
        return $defaultMap;
    }
    /**
     * {@inheritdoc}
     */
    public function isHandling(array $record) : bool
    {
        // bodge for debug level as needs to always call that;
        // WP_CLI deals with --debug command argument
        $level = (int) $record['level'];
        if ($level == Logger::DEBUG) {
            return \true;
        }
        // check level is one we know how to handle as more could be added in the future
        // that would need mapping to the WP-CLI:: method.
        $supported = self::getSupportedLevels($this->getLoggerMap());
        $isSupported = \in_array($level, $supported, \true);
        return $isSupported && parent::isHandling($record);
    }
    /**
     * Returns a list of supported Logger levels based on the supplied logger map.
     *
     * @param array $map Logger map containing mappings.
     *
     * @return array Array of supported Logger levels.
     */
    public static function getSupportedLevels(array $map)
    {
        $results = [];
        // validate the supplied map and return only the valid levels
        $levels = \array_keys($map);
        foreach ($levels as $level) {
            try {
                self::validateLoggerMap($map, $level, Logger::getLevelName($level));
                $results[] = $level;
            } catch (\Exception $e) {
                // do nothing
            }
        }
        return $results;
    }
    /**
     * Writes the record down to the log of the implementing handler
     *
     * @see https://seldaek.github.io/monolog/doc/message-structure.html
     *
     * @param  array $record
     *
     * @return void
     * @throws \RuntimeException If a level is passed that is not currently mapped to a WP_CLI:: method.
     * @throws \InvalidArgumentException if something in $record is invalid.
     */
    protected function write(array $record) : void
    {
        // init vars for whatever being used
        $level = (int) $record['level'];
        // no default as think it would be an error to be empty
        $levelName = (string) $record['level_name'] ?: '';
        $formattedMessage = $this->getFormatter()->format($record);
        // build up details of calling method
        $loggerMap = $this->getLoggerMap();
        self::validateLoggerMap($loggerMap, $level, $levelName);
        $method = $loggerMap[$level]['method'];
        $includeLevelName = isset($loggerMap[$level]['includeLevelName']) ? (bool) $loggerMap[$level]['includeLevelName'] : \false;
        $exit = isset($loggerMap[$level]['exit']) ? (bool) $loggerMap[$level]['exit'] : \false;
        if ($includeLevelName) {
            $logMessage = '(' . $levelName . ') ' . $formattedMessage;
        } else {
            $logMessage = $formattedMessage;
        }
        // call it
        if ($method != 'error') {
            WP_CLI::$method($logMessage);
        } else {
            WP_CLI::$method($logMessage, $exit);
        }
    }
    /**
     * Sanity check a logger map.
     *
     * Checks to make sure the logger map contains a supported log level and has an existing method in WP_CLI.
     *
     * @param array $map The logger map to be checked.
     * @param int $level The level to be checked.
     * @param string $levelName The name of the level for error reporting.
     *
     * @throws \InvalidArgumentException
     */
    public static function validateLoggerMap(array $map, int $level, string $levelName = '')
    {
        $entry = isset($map[(string) $level]) ? $map[(string) $level] : array();
        if (empty($entry)) {
            throw new \InvalidArgumentException('Logger map has no entry for level ' . $levelName . '(' . $level . ')');
        }
        if (!\method_exists('WP_CLI', $entry['method'])) {
            throw new \InvalidArgumentException('Logger map contains an invalid method for level ' . $levelName . '(' . $level . ')');
        }
        if ($entry['method'] !== 'error' && isset($entry['exit']) && $entry['exit'] === \true) {
            throw new \InvalidArgumentException('Logger map for level ' . $levelName . '(' . $level . ') specifies exit but
                         exit is only valid for \'error\' method');
        }
    }
    /**
     * Validates every entry in a logger map.
     *
     * @param array $map The logger map to validate.
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    public static function validateAllLoggerMapEntries(array $map) : void
    {
        foreach (\array_keys($map) as $level) {
            $level = (int) $level;
            self::validateLoggerMap($map, $level, Logger::getLevelName($level));
        }
    }
    /**
     * Returns the Logger map.
     *
     * @return array Logger map/
     */
    protected function getLoggerMap()
    {
        if (!$this->loggerMap) {
            $this->loggerMap = self::getDefaultLoggerMap();
        }
        return $this->loggerMap;
    }
    /***
     * Returns an array of default mappings to map Logger methods to WP-CLI methods.
     *
     * @return array
     */
    public static function getDefaultLoggerMap()
    {
        return [Logger::DEBUG => ['method' => 'debug'], Logger::INFO => ['method' => 'log'], Logger::NOTICE => ['method' => 'log', 'includeLevelName' => \true], Logger::WARNING => ['method' => 'warning', 'includeLevelName' => \true], Logger::ERROR => ['method' => 'error', 'includeLevelName' => \true, 'exit' => \false], Logger::CRITICAL => ['method' => 'error', 'includeLevelName' => \true, 'exit' => \true], Logger::ALERT => ['method' => 'error', 'includeLevelName' => \true, 'exit' => \true], Logger::EMERGENCY => ['method' => 'error', 'includeLevelName' => \true, 'exit' => \true]];
    }
    /**
     * {@inheritdoc}
     */
    protected function getDefaultFormatter() : FormatterInterface
    {
        if ($this->verbose) {
            return new LineFormatter(self::WP_CLI_FORMAT_VERBOSE);
        } else {
            return new LineFormatter(self::WP_CLI_FORMAT_STANDARD);
        }
    }
}
