<?php
/**
 * Syslog log writer.
 *
 * @package    KO7
 * @category   Logging
 * @author     Jeremy Bush
 * @copyright  (c) 2007-2016  Kohana Team
 * @copyright  (c) since 2016 Koseven Team
 * @license    https://koseven.ga/LICENSE
 */

namespace KO7\Log;

class Syslog extends Writer
{

    /**
     * @var  string  The syslog identifier
     */
    protected $_ident;

    /**
     * Creates a new syslog logger.
     *
     * @link    http://www.php.net/manual/function.openlog
     *
     * @param string $ident syslog identifier
     * @param int $facility facility to log to
     */
    public function __construct(string $ident = 'KO7PHP', int $facility = LOG_USER)
    {
        $this->_ident = $ident;

        // Open the connection to syslog
        openlog($this->_ident, LOG_CONS, $facility);
    }

    /**
     * Writes each of the messages into the syslog.
     *
     * @param array $messages
     * @return  void
     */
    public function write(array $messages): void
    {
        foreach ($messages as $message) {
            syslog($message['level'], $message['body']);

            if (isset($message['additional']['exception'])) {
                syslog(static::$strace_level, $message['additional']['exception']->getTraceAsString());
            }
        }
    }

    /**
     * Closes the syslog connection
     */
    public function __destruct()
    {
        // Close connection to syslog
        closelog();
    }

}
