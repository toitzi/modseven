<?php
/**
 * Message logging with observer-based log writing.
 *
 * [!!] This class does not support extensions, only additional writers.
 *
 * @package    KO7
 * @category   Logging
 *
 * @copyright  (c) 2007-2016  Kohana Team
 * @copyright  (c) since 2016 Koseven Team
 * @license    https://koseven.ga/LICENSE
 */

namespace KO7;

class Log
{

    // Log message levels - Windows users see PHP Bug #18090
    public const EMERGENCY = LOG_EMERG;    // 0
    public const ALERT = LOG_ALERT;    // 1
    public const CRITICAL = LOG_CRIT;     // 2
    public const ERROR = LOG_ERR;      // 3
    public const WARNING = LOG_WARNING;  // 4
    public const NOTICE = LOG_NOTICE;   // 5
    public const INFO = LOG_INFO;     // 6
    public const DEBUG = LOG_DEBUG;    // 7

    /**
     * @var  boolean  immediately write when logs are added
     */
    public static $write_on_add = false;

    /**
     * @var  Log  Singleton instance container
     */
    protected static $_instance;
    /**
     * @var  array  list of added messages
     */
    protected $_messages = [];
    /**
     * @var  array  list of log writers
     */
    protected $_writers = [];

    /**
     * Get the singleton instance of this class and enable writing at shutdown.
     *
     * @return  Log
     */
    public static function instance(): Log
    {
        if (static::$_instance === NULL) {
            // Create a new instance
            static::$_instance = new self;

            // Write the logs at shutdown
            register_shutdown_function([static::$_instance, 'write']);
        }

        return static::$_instance;
    }

    /**
     * Attaches a log writer, and optionally limits the levels of messages that
     * will be written by the writer.
     *
     * @param Log\Writer $writer instance
     * @param mixed $levels array of messages levels to write OR max level to write
     * @param integer $min_level min level to write IF $levels is not an array
     * @return  self
     */
    public function attach(Log\Writer $writer, $levels = [], int $min_level = 0): self
    {
        if (!is_array($levels)) {
            $levels = range($min_level, $levels);
        }

        $this->_writers[(string)$writer] = [
            'object' => $writer,
            'levels' => $levels
        ];

        return $this;
    }

    /**
     * Detaches a log writer. The same writer object must be used.
     *
     * @param Log\Writer $writer instance
     * @return  self
     */
    public function detach(Log\Writer $writer): self
    {
        // Remove the writer
        unset($this->_writers[(string)$writer]);

        return $this;
    }

    /**
     * Adds a message to the log. Replacement values must be passed in to be
     * replaced using [strtr](http://php.net/strtr).
     *
     * @param integer $level level of message
     * @param string $message message body
     * @param array $values values to replace in the message
     * @param array $additional additional custom parameters to supply to the log writer
     * @return  self
     */
    public function add(int $level, string $message, ?array $values = NULL, ?array $additional = NULL): self
    {
        if ($values) {
            // Insert the values into the message
            $message = strtr($message, $values);
        }

        // Grab a copy of the trace
        if (isset($additional['exception'])) {
            $trace = $additional['exception']->getTrace();
        } else {
            // Older php version don't have 'DEBUG_BACKTRACE_IGNORE_ARGS', so manually remove the args from the backtrace
            if (!defined('DEBUG_BACKTRACE_IGNORE_ARGS')) {
                $trace = array_map(static function ($item) {
                    unset($item['args']);
                    return $item;
                }, array_slice(debug_backtrace(FALSE), 1));
            } else {
                $trace = array_slice(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), 1);
            }
        }

        if ($additional === NULL) {
            $additional = [];
        }

        // Create a new message
        $this->_messages[] = [
            'time' => time(),
            'level' => $level,
            'body' => $message,
            'trace' => $trace,
            'file' => $trace[0]['file'] ?? null,
            'line' => $trace[0]['line'] ?? null,
            'class' => $trace[0]['class'] ?? null,
            'function' => $trace[0]['function'] ?? null,
            'additional' => $additional,
        ];

        if (static::$write_on_add) {
            // Write logs as they are added
            $this->write();
        }

        return $this;
    }

    /**
     * Write and clear all of the messages.
     *
     * @return  void
     */
    public function write(): void
    {
        if (empty($this->_messages)) {
            // There is nothing to write, move along
            return;
        }

        // Import all messages locally
        $messages = $this->_messages;

        // Reset the messages array
        $this->_messages = [];

        foreach ($this->_writers as $writer) {
            if (empty($writer['levels'])) {
                // Write all of the messages
                $writer['object']->write($messages);
            } else {
                // Filtered messages
                $filtered = [];

                foreach ($messages as $message) {
                    if (in_array($message['level'], $writer['levels'], true)) {
                        // Writer accepts this kind of message
                        $filtered[] = $message;
                    }
                }

                // Write the filtered messages
                $writer['object']->write($filtered);
            }
        }
    }

}
