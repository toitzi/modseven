<?php
/**
 * Log writer abstract class. All [Log] writers must extend this class.
 *
 * @package    KO7
 * @category   Logging
 *
 * @copyright  (c) 2007-2016  Kohana Team
 * @copyright  (c) since 2016 Koseven Team
 * @license    https://koseven.ga/LICENSE
 */

namespace KO7\Log;

use \KO7\Date;

abstract class Writer
{

    /**
     * @var  string  timestamp format for log entries.
     *
     * Defaults to Date::$timestamp_format
     */
    public static $timestamp;

    /**
     * @var  string  timezone for log entries
     *
     * Defaults to Date::$timezone, which defaults to date_default_timezone_get()
     */
    public static $timezone;
    /**
     * @var  int  Level to use for stack traces
     */
    public static $strace_level = LOG_DEBUG;
    /**
     * Numeric log level to string lookup table.
     * @var array
     */
    protected $_log_levels = [
        LOG_EMERG => 'EMERGENCY',
        LOG_ALERT => 'ALERT',
        LOG_CRIT => 'CRITICAL',
        LOG_ERR => 'ERROR',
        LOG_WARNING => 'WARNING',
        LOG_NOTICE => 'NOTICE',
        LOG_INFO => 'INFO',
        LOG_DEBUG => 'DEBUG',
    ];

    /**
     * Write an array of messages.
     *
     * @param array $messages
     * @return  void
     */
    abstract public function write(array $messages): void;

    /**
     * Allows the writer to have a unique key when stored.
     *
     * @return  string
     */
    final public function __toString(): string
    {
        return spl_object_hash($this);
    }

    /**
     * Formats a log entry.
     *
     * @param array $message
     * @param string $format
     * @return  string
     */
    public function format_message(array $message, string $format = 'time --- level: body in file:line'): string
    {
        $message['time'] = Date::formatted_time('@' . $message['time'], static::$timestamp, static::$timezone, TRUE);
        $message['level'] = $this->_log_levels[$message['level']];

        $string = strtr($format, array_filter($message, 'is_scalar'));

        if (isset($message['additional']['exception'])) {
            // Re-use as much as possible, just resetting the body to the trace
            $message['body'] = $message['additional']['exception']->getTraceAsString();
            $message['level'] = $this->_log_levels[static::$strace_level];

            $string .= PHP_EOL . strtr($format, array_filter($message, 'is_scalar'));
        }

        return $string;
    }

}
