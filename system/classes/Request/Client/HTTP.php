<?php
/**
 * HTTP driver performs external requests using the
 * pecl_http extension.
 *
 * NOTE: This driver is not used by default. To use it as default call:
 *
 * @package        KO7\Request
 *
 * @copyright  (c) 2007-2016  Kohana Team
 * @copyright  (c) since 2016 Koseven Team
 * @license        https://koseven.ga/LICENSE
 *
 */

namespace KO7\Request\Client;

use \KO7\Request;
use \KO7\Request\Exception;
use \KO7\Response;

class HTTP extends External
{

    /**
     * Sends the HTTP message [Request] to a remote server and processes
     * the response.
     *
     * @param Request $request request to send
     * @param Response $response response to send
     *
     * @return Response
     * @throws Exception
     *
     */
    public function _send_message(Request $request, Response $response): Response
    {
        // Instance a new Client
        $client = new \http\Client;

        // Process cookies
        if ($cookies = $request->cookie()) {
            $client->setCookies($cookies);
        }

        // Instance HTTP Request Object
        $http_request = new \http\Client\Request($request->method(), $request->uri());

        // Set custom cURL options
        if ($this->_options) {
            $http_request->setOptions($this->_options);
        }

        // Set headers
        if (!empty($headers = $request->headers()->getArrayCopy())) {
            $http_request->setHeaders($headers);
        }

        // Set query (?foo=bar&bar=foo)
        if ($query = $request->query()) {
            $http_request->setQuery($query);
        }

        // Set the body
        // This will also add a Content-Type: application/x-www-form-urlencoded header unless you override it
        if ($body = $request->body()) {
            $http_request->getBody()->append($body);
        }

        // Execute call, will throw an Runtime Exception if a stream is not available
        try {
            $client->enqueue($http_request)->send();
        } catch (\http\Exception\RuntimeException $e) {
            throw new Exception($e->getMessage());
        }

        // Parse Response
        $http_response = $client->getResponse();

        // Build the response
        if ($http_response !== null) {
            $response
                ->status($http_response->getResponseCode())
                ->headers($http_response->getHeaders())
                ->cookie($http_response->getCookies())
                ->body($http_response->getBody());
        }

        return $response;
    }

}
