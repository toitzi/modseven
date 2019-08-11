<?php
/**
 * STDOUT log writer. Writes out messages to STDOUT.
 *
 * @package    KO7
 * @category   Logging
 *
 * @copyright  (c) 2007-2016  Kohana Team
 * @copyright  (c) since 2016 Koseven Team
 * @license    https://koseven.ga/LICENSE
 */

namespace KO7\Log;

class StdOut extends Writer
{

    /**
     * Writes each of the messages to STDOUT.
     *
     * @param array $messages
     * @return  void
     */
    public function write(array $messages): void
    {
        foreach ($messages as $message) {
            // Writes out each message
            fwrite(STDOUT, $this->format_message($message) . PHP_EOL);
        }
    }

}
