<?php
/**
 * STDERR log writer. Writes out messages to STDERR.
 *
 * @copyright  (c) 2007-2016  Kohana Team
 * @copyright  (c) since 2016 Koseven Team
 * @license        https://koseven.ga/LICENSE
 */

namespace KO7\Log;

class StdErr extends Writer {

    /**
     * Writes the message to STDERR.
     *
     * @param string $message
     */
    public function write(string $message) : void
    {
        fwrite(STDERR, $message.PHP_EOL);
    }

}
