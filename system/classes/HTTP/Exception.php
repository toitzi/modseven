<?php

namespace Modseven\HTTP;

class Exception extends \Modseven\Exception
{

    /**
     * @var  int        http status code
     */
    protected $_code = 0;

    /**
     * @var  \Modseven\Request    Request instance that triggered this exception.
     */
    protected $_request;

    /**
     * Exception constructor.
     *
     * @param integer $code the http status code
     * @param string $message status message, custom content to display with error
     * @param array $variables translation variables
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = '', ?array $variables = NULL, int $code = 0, \Throwable $previous = NULL)
    {
        $this->_code = $code;
        parent::__construct($message, $variables, $code, $previous);
    }

    /**
     * Creates an HTTP_Exception of the specified type.
     *
     * @param integer $code the http status code
     * @param string $message status message, custom content to display with error
     * @param array $variables translation variables
     * @return  Exception
     */
    public static function factory(int $code, ?string $message = NULL, ?array $variables = NULL, \Exception $previous = NULL): Exception
    {
        return new self($message, $variables, $code, $previous);
    }

    /**
     * Store the Request that triggered this exception.
     *
     * @param \Modseven\Request $request Request object that triggered this exception.
     * @return  self|Request
     */
    public function request(\Modseven\Request $request = NULL)
    {
        if ($request === NULL) {
            return $this->_request;
        }

        $this->_request = $request;

        return $this;
    }

    /**
     * Generate a Response for the current Exception
     *
     * @return \Modseven\Response
     */
    public function get_response(): \Modseven\Response
    {
        return \Modseven\Exception::response($this);
    }

}
