<?php
/**
 * HTTP Caching adaptor class that provides caching services to the
 * [Request_Client] class, using HTTP cache control logic as defined in
 * RFC 2616.
 *
 * @package    Kohana
 * @category   Base
 *
 * @copyright  (c) 2007-2016  Kohana Team
 * @copyright  (c) since 2016 Koseven Team
 * @license    https://koseven.ga/LICENSE
 * @since      3.2.0
 */

namespace KO7\Cache;

use \KO7\Arr;
use \KO7\Cache;
use \KO7\Cache\Arithmetic;
use \KO7\Exception;
use \KO7\Request;
use \KO7\Request\Client;
use \KO7\Response;
use \KO7\HTTP\Header;
use \KO7\HTTP\Request as HTTP_Request;

class HTTP
{

    public const CACHE_STATUS_KEY = 'x-cache-status';
    public const CACHE_STATUS_SAVED = 'SAVED';
    public const CACHE_STATUS_HIT = 'HIT';
    public const CACHE_STATUS_MISS = 'MISS';
    public const CACHE_HIT_KEY = 'x-cache-hits';
    /**
     * @var     Cache    cache driver to use for HTTP caching
     */
    protected $_cache;
    /**
     * @var    callable  Cache key generator callback
     */
    protected $_cache_key_callback;
    /**
     * @var    boolean   Defines whether this client should cache `private` cache directives
     * @link   http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.9
     */
    protected $_allow_private_cache = false;
    /**
     * @var    int       The timestamp of the request
     */
    protected $_request_time;
    /**
     * @var    int       The timestamp of the response
     */
    protected $_response_time;

    /**
     * Constructor method for this class. Allows dependency injection of the
     * required components such as `Cache` and the cache key generator.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        foreach ($options as $key => $value) {
            if (method_exists($this, $key)) {
                $this->$key($value);
            }
        }

        if ($this->_cache_key_callback === NULL) {
            $this->cache_key_callback('HTTP_Cache::basic_cache_key_generator');
        }
    }

    /**
     * Sets or gets the cache key generator callback for this caching
     * class. The cache key generator provides a unique hash based on the
     * `Request` object passed to it.
     *
     * The default generator is [HTTP_Cache::basic_cache_key_generator()], which
     * serializes the entire `HTTP_Request` into a unique sha1 hash. This will
     * provide basic caching for static and simple dynamic pages. More complex
     * algorithms can be defined and then passed into `HTTP_Cache` using this
     * method.
     *
     * @param callback $callback
     * @return  mixed
     * @throws  Exception
     */
    public function cache_key_callback($callback = NULL)
    {
        if ($callback === NULL) {
            return $this->_cache_key_callback;
        }

        if (!is_callable($callback)) {
            throw new Exception('cache_key_callback must be callable!');
        }

        $this->_cache_key_callback = $callback;
        return $this;
    }

    /**
     * Factory method for HTTP_Cache that provides a convenient dependency
     * injector for the Cache library.
     *
     * @param mixed $cache cache engine to use
     * @param array $options options to set to this class
     * @return  self
     * @uses    Cache
     */
    public static function factory($cache, array $options = []): self
    {
        if (!$cache instanceof Cache) {
            $cache = Cache::instance($cache);
        }

        $options['cache'] = $cache;

        return new self($options);
    }

    /**
     * Executes the supplied [Request] with the supplied [Request_Client].
     * Before execution, the HTTP_Cache adapter checks the request type,
     * destructive requests such as `POST`, `PUT` and `DELETE` will bypass
     * cache completely and ensure the response is not cached. All other
     * Request methods will allow caching, if the rules are met.
     *
     * @param Client $client client to execute with Cache-Control
     * @param Request $request request to execute with client
     * @return  [Response]
     */
    public function execute(Client $client, Request $request, Response $response)
    {
        if (!$this->_cache instanceof Cache)
            return $client->execute_request($request, $response);

        // If this is a destructive request, by-pass cache completely
        if (in_array($request->method(), [
            HTTP_Request::POST,
            HTTP_Request::PUT,
            HTTP_Request::DELETE
        ], true)) {
            // Kill existing caches for this request
            $this->invalidate_cache($request);

            $response = $client->execute_request($request, $response);

            $cache_control = Header::create_cache_control([
                'no-cache',
                'must-revalidate'
            ]);

            // Ensure client respects destructive action
            return $response->headers('cache-control', $cache_control);
        }

        // Create the cache key
        $cache_key = $this->create_cache_key($request, $this->_cache_key_callback);

        // Try and return cached version
        if (($cached_response = $this->cache_response($cache_key, $request)) instanceof Response)
            return $cached_response;

        // Start request time
        $this->_request_time = time();

        // Execute the request with the Request client
        $response = $client->execute_request($request, $response);

        // Stop response time
        $this->_response_time = (time() - $this->_request_time);

        // Cache the response
        $this->cache_response($cache_key, $request, $response);

        $response->headers(static::CACHE_STATUS_KEY,
            static::CACHE_STATUS_MISS);

        return $response;
    }

    /**
     * Invalidate a cached response for the [Request] supplied.
     * This has the effect of deleting the response from the
     * [Cache] entry.
     *
     * @param Request $request Response to remove from cache
     * @return  void
     */
    public function invalidate_cache(Request $request): void
    {
        if (($cache = $this->cache()) instanceof Cache) {
            $cache->delete($this->create_cache_key($request, $this->_cache_key_callback));
        }

        return;
    }

    /**
     * Getter and setter for the internal caching engine,
     * used to cache responses if available and valid.
     *
     * @param Cache $cache engine to use for caching
     * @return  Cache|self
     */
    public function cache(?Cache $cache = NULL)
    {
        if ($cache === NULL) {
            return $this->_cache;
        }

        $this->_cache = $cache;
        return $this;
    }

    /**
     * Creates a cache key for the request to use for caching
     * [KO7_Response] returned by [Request::execute].
     *
     * This is the default cache key generating logic, but can be overridden
     * by setting [HTTP_Cache::cache_key_callback()].
     *
     * @param Request $request request to create key for
     * @param callback $callback optional callback to use instead of built-in method
     * @return  string
     */
    public function create_cache_key(Request $request, $callback = NULL): string
    {
        if (is_callable($callback)) {
            return $callback($request);
        }
        return self::basic_cache_key_generator($request);

    }

    /**
     * Basic cache key generator that hashes the entire request and returns
     * it. This is fine for static content, or dynamic content where user
     * specific information is encoded into the request.
     *
     * @param Request $request
     * @return  string
     */
    public static function basic_cache_key_generator(Request $request): string
    {
        $uri = $request->uri();
        $query = $request->query();
        $headers = $request->headers()->getArrayCopy();
        $body = $request->body();

        return sha1($uri . '?' . http_build_query($query, NULL, '&') . '~' . implode('~', $headers) . '~' . $body);
    }

    /**
     * Caches a [Response] using the supplied [Cache]
     * and the key generated by [Request_Client::_create_cache_key].
     *
     * If not response is supplied, the cache will be checked for an existing
     * one that is available.
     *
     * @param string $key the cache key to use
     * @param Request $request the HTTP Request
     * @param Response $response the HTTP Response
     * @return  mixed
     */
    public function cache_response(string $key, Request $request, Response $response = NULL)
    {
        if (!$this->_cache instanceof Cache) {
            return false;
        }

        // Check for Pragma: no-cache
        if ($pragma = $request->headers('pragma')) {
            if ($pragma === 'no-cache') {
                return false;
            }
            if (is_array($pragma) AND in_array('no-cache', $pragma)) {
                return false;
            }
        }

        // If there is no response, lookup an existing cached response
        if ($response === NULL) {
            $response = $this->_cache->get($key);

            if (!$response instanceof Response) {
                return false;
            }

            // Do cache hit arithmetic, using fast arithmetic if available
            if ($this->_cache instanceof Arithmetic) {
                $hit_count = $this->_cache->increment(static::CACHE_HIT_KEY . $key);
            } else {
                $hit_count = $this->_cache->get(static::CACHE_HIT_KEY . $key);
                $this->_cache->set(static::CACHE_HIT_KEY . $key, ++$hit_count);
            }

            // Update the header to have correct HIT status and count
            $response->headers(static::CACHE_STATUS_KEY,
                static::CACHE_STATUS_HIT)
                ->headers(static::CACHE_HIT_KEY, $hit_count);

            return $response;
        }

        if (($ttl = $this->cache_lifetime($response)) === FALSE) {
            return false;
        }

        $response->headers(static::CACHE_STATUS_KEY,
            static::CACHE_STATUS_SAVED);

        // Set the hit count to zero
        $this->_cache->set(static::CACHE_HIT_KEY . $key, 0);

        return $this->_cache->set($key, $response, $ttl);
    }

    /**
     * Calculates the total Time To Live based on the specification
     * RFC 2616 cache lifetime rules.
     *
     * @param Response $response Response to evaluate
     * @return  mixed  TTL value or false if the response should not be cached
     */
    public function cache_lifetime(Response $response)
    {
        // Get out of here if this cannot be cached
        if (!$this->set_cache($response)) {
            return false;
        }

        // Calculate apparent age
        if ($date = $response->headers('date')) {
            $apparent_age = max(0, $this->_response_time - strtotime($date));
        } else {
            $apparent_age = max(0, $this->_response_time);
        }

        // Calculate corrected received age
        if ($age = $response->headers('age')) {
            $corrected_received_age = max($apparent_age, intval($age));
        } else {
            $corrected_received_age = $apparent_age;
        }

        // Corrected initial age
        $corrected_initial_age = $corrected_received_age + $this->request_execution_time();

        // Resident time
        $resident_time = time() - $this->_response_time;

        // Current age
        $current_age = $corrected_initial_age + $resident_time;

        // Prepare the cache freshness lifetime
        $ttl = NULL;

        // Cache control overrides
        if ($cache_control = $response->headers('cache-control')) {
            // Parse the cache control header
            $cache_control = Header::parse_cache_control($cache_control);

            if (isset($cache_control['max-age'])) {
                $ttl = $cache_control['max-age'];
            }

            if ($this->_allow_private_cache && isset($cache_control['s-maxage'], $cache_control['private'])) {
                $ttl = $cache_control['s-maxage'];
            }

            if (isset($cache_control['max-stale']) && !isset($cache_control['must-revalidate'])) {
                $ttl = $current_age + $cache_control['max-stale'];
            }
        }

        // If we have a TTL at this point, return
        if ($ttl !== NULL) {
            return $ttl;
        }

        if ($expires = $response->headers('expires')) {
            return strtotime($expires) - $current_age;
        }

        return FALSE;
    }

    /**
     * Controls whether the response can be cached. Uses HTTP
     * protocol to determine whether the response can be cached.
     *
     * @link    http://www.w3.org/Protocols/rfc2616/rfc2616.html RFC 2616
     * @param Response $response The Response
     * @return  boolean
     */
    public function set_cache(Response $response): bool
    {
        $headers = $response->headers()->getArrayCopy();

        if ($cache_control = Arr::get($headers, 'cache-control')) {
            // Parse the cache control
            $cache_control = Header::parse_cache_control($cache_control);

            // If the no-cache or no-store directive is set, return
            if (array_intersect($cache_control, ['no-cache', 'no-store'])) {
                return false;
            }

            // Check for private cache and get out of here if invalid
            if (!$this->_allow_private_cache && in_array('private', $cache_control, true)) {
                if (!isset($cache_control['s-maxage'])) {
                    return false;
                }

                // If there is a s-maxage directive we can use that
                $cache_control['max-age'] = $cache_control['s-maxage'];
            }

            // Check that max-age has been set and if it is valid for caching
            if (isset($cache_control['max-age']) && $cache_control['max-age'] < 1) {
                return false;
            }
        }

        // Can't cache things that have expired already
        return !(!isset($cache_control['max-age'])
            && ($expires = Arr::get($headers, 'expires'))
            && strtotime($expires) <= time());
    }

    /**
     * Returns the duration of the last request execution.
     * Either returns the time of completed requests or
     * `FALSE` if the request hasn't finished executing, or
     * is yet to be run.
     *
     * @return  mixed
     */
    public function request_execution_time()
    {
        if ($this->_request_time === NULL OR $this->_response_time === NULL) {
            return false;
        }

        return $this->_response_time - $this->_request_time;
    }

    /**
     * Gets or sets the [Request_Client::allow_private_cache] setting.
     * If set to `TRUE`, the client will also cache cache-control directives
     * that have the `private` setting.
     *
     * @link    http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.9
     * @param boolean $setting allow caching of privately marked responses
     * @return  boolean|self
     */
    public function allow_private_cache(?bool $setting = NULL)
    {
        if ($setting === NULL) {
            return $this->_allow_private_cache;
        }

        $this->_allow_private_cache = (bool)$setting;
        return $this;
    }

}