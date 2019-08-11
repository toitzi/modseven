<?php
/**
 * File log writer. Writes out messages and stores them in a YYYY/MM directory.
 *
 * @package    KO7
 * @category   Logging
 *
 * @copyright  (c) 2007-2016  Kohana Team
 * @copyright  (c) since 2016 Koseven Team
 * @license    https://koseven.ga/LICENSE
 */

namespace KO7\Log;

use \KO7\Debug;
use \KO7\Exception;

class File extends Writer
{

    /**
     * @var  string  Directory to place log files in
     */
    protected $_directory;

    /**
     * Creates a new file logger. Checks that the directory exists and
     * is writable.
     *
     * @param string $directory log directory
     */
    public function __construct(string $directory)
    {
        if (!is_dir($directory) || !is_writable($directory)) {
            throw new Exception('Directory :dir must be writable',
                [':dir' => Debug::path($directory)]);
        }

        // Determine the directory path
        $this->_directory = realpath($directory) . DIRECTORY_SEPARATOR;
    }

    /**
     * Writes each of the messages into the log file. The log file will be
     * appended to the `YYYY/MM/DD.log.php` file, where YYYY is the current
     * year, MM is the current month, and DD is the current day.
     *
     * @param array $messages
     * @return  void
     */
    public function write(array $messages): void
    {
        // Set the yearly directory name
        $directory = $this->_directory . date('Y');

        if (!is_dir($directory)) {
            // Create the yearly directory
            if (!mkdir($directory, 02777) && !is_dir($directory)) {
                throw new Exception('Directory ":dir" was not created', [
                    ':dir' => $directory
                ]);
            }

            // Set permissions (must be manually set to fix umask issues)
            chmod($directory, 02777);
        }

        // Add the month to the directory
        $directory .= DIRECTORY_SEPARATOR . date('m');

        if (!is_dir($directory)) {
            // Create the monthly directory
            if (!mkdir($directory, 02777) && !is_dir($directory)) {
                throw new Exception('Directory ":dir" was not created', [
                    ':dir' => $directory
                ]);
            }

            // Set permissions (must be manually set to fix umask issues)
            chmod($directory, 02777);
        }

        // Set the name of the log file
        $filename = $directory . DIRECTORY_SEPARATOR . date('d') . '.php';

        if (!file_exists($filename)) {
            // Create the log file
            file_put_contents($filename, NULL, LOCK_EX);

            // Allow anyone to write to log files
            chmod($filename, 0666);
        }

        foreach ($messages as $message) {
            // Write each message into the log file
            file_put_contents($filename, PHP_EOL . $this->format_message($message), FILE_APPEND);
        }
    }

}
