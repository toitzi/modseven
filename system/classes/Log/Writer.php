<?php
/**
 * Log writer abstract class. All [Log] writers must extend this class.
 *
 * @copyright  (c) 2007-2016  Kohana Team
 * @copyright  (c) since 2016 Koseven Team
 * @license        https://koseven.ga/LICENSE
 */

namespace KO7\Log;

use \KO7\Log;
use \KO7\Date;

abstract class Writer {

    /**
     * Timestamp format for log entries.
     * @var string
     */
    public static ?string $timestamp = null;

    /**
     * Timezone for log entries.
     * @var string
     */
    public static ?string $timezone = null;

    /**
     * Level to use for stack traces
     * @var string
     */
    public static string $strace_level;

    /**
     * Write message
     *
     * @param string $message
     */
    abstract public function write(string $message) : void;

    /**
     * Allows the writer to have a unique key when stored.
     *
     * @return  string
     */
    final public function __toString() : string
    {
        return spl_object_hash($this);
    }

    /**
     * Formats a log entry to a loggable line.
     * This method is automatically executed by the logger class.
     * If you need a different format in your writer overwrite this function.
     *
     * @param array  $message   Message to log
     * @param string $format    Format of the log line
     *
     * @throws \Exception
     *
     * @return  string
     */
    public function format_message(array $message, string $format = 'time --- level: body in file:line') : string
    {
        $message['time'] = Date::formatted_time('@'.$message['time'], static::$timestamp, static::$timezone);

        $string = strtr($format, array_filter($message, 'is_scalar'));

        if (isset($message['context']['exception']))
        {
            // Re-use as much as possible, just resetting the body to the trace
            $message['body'] = $message['context']['exception']->getTraceAsString();
            $message['level'] = static::$strace_level ?? Log::DEBUG;

            $string .= PHP_EOL.strtr($format, array_filter($message, 'is_scalar'));
        }

        return $string;
    }

}
