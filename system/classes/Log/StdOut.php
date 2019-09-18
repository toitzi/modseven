<?php
/**
 * STDOUT log writer. Writes out messages to STDOUT.
 *
 * @copyright  (c) 2007-2016  Kohana Team
 * @copyright  (c) since 2016 Koseven Team
 * @license        https://koseven.ga/LICENSE
 */

namespace KO7\Log;

class StdOut extends Writer {

    /**
     * Writes the message to STDOUT.
     *
     * @param string $message
     */
    public function write(string $message) : void
    {
        fwrite(STDOUT, $message.PHP_EOL);
    }

}
