<?php
/**
 * KO7 Error Exception Class
 */

namespace KO7\Error;

use \KO7\I18n;

class Exception extends \ErrorException
{

    /**
     * Creates a new translated exception.
     *
     * @param string $message Error message
     * @param array $variables Translation variables
     * @param int $code The error code
     * @param int $severity The severity level of the exception.
     * @param string $file The filename where the exception is thrown.
     * @param int $line The line number where the exception is thrown.
     * @param \Throwable $previous Previous throwable
     * @return  void
     */
    public function __construct(string $message = '', ?array $variables = NULL, int $code = 0, int $severity = 1, string $file = __FILE__, int $line = __LINE__, \Throwable $previous = NULL)
    {
        // Set the message
        $message = I18n::get([$message, $variables]);

        // Pass the message and integer code to the parent
        parent::__construct($message, $code, $severity, $file, $line, $previous);
    }

}
