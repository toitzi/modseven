<?php
/**
 * KO7 Cache provides a common interface to a variety of caching engines. Tags are
 * supported where available natively to the cache system. KO7 Cache supports multiple
 * instances of cache engines through a grouped singleton pattern.
 *
 * @package    KO7/Cache
 * @category   Base
 * @version    2.0
 *
 * @copyright  (c) 2007-2016  Kohana Team
 * @copyright  (c) since 2016 Koseven Team
 * @license    https://koseven.ga/LICENSE
 */

namespace KO7;

use \KO7\Cache\Exception;

abstract class Cache
{

    public const DEFAULT_EXPIRE = 3600;

    /**
     * @var   string     default driver to use
     */
    public static $default = 'file';

    /**
     * @var array Cache instances
     */
    public static $instances = [];
    /**
     * @var array Config
     */
    protected $_config = [];

    /**
     * Ensures singleton pattern is observed, loads the default expiry
     *
     * @param array $config configuration
     */
    protected function __construct(array $config)
    {
        $this->config($config);
    }

    /**
     * Getter and setter for the configuration. If no argument provided, the
     * current configuration is returned. Otherwise the configuration is set
     * to this class.
     *
     * @param mixed    key to set to array, either array or config path
     * @param mixed    value to associate with key
     * @return  mixed
     */
    public function config($key = NULL, $value = NULL)
    {
        if ($key === NULL) {
            return $this->_config;
        }

        if (is_array($key)) {
            $this->_config = $key;
        } else {
            if ($value === NULL) {
                return Arr::get($this->_config, $key);
            }

            $this->_config[$key] = $value;
        }

        return $this;
    }

    /**
     * Creates a singleton of a KO7 Cache group. If no group is supplied
     * the __default__ cache group is used.
     *
     * @param string $group the name of the cache group to use [Optional]
     * @return  Cache
     * @throws  Exception
     */
    public static function instance(?string $group = NULL): Cache
    {
        // If there is no group supplied, try to get it from the config
        if ($group === NULL) {
            $group = Core::$config->load('cache.default');
        }

        // If there is no group supplied
        if ($group === NULL) {
            // Use the default setting
            $group = static::$default;
        }

        if (isset(static::$instances[$group])) {
            // Return the current group if initiated already
            return static::$instances[$group];
        }

        $config = Core::$config->load('cache');

        if (!$config->offsetExists($group)) {
            throw new Exception(
                'Failed to load KO7 Cache group: :group',
                [':group' => $group]
            );
        }

        $config = $config->get($group);

        // Create a new cache type instance
        $cache_class = 'Cache_' . ucfirst($config['driver']);
        static::$instances[$group] = new $cache_class($config);

        // Return the instance
        return static::$instances[$group];
    }

    /**
     * Overload the __clone() method to prevent cloning
     *
     * @throws  Exception
     */
    final public function __clone()
    {
        throw new Exception('Cloning of Cache objects is forbidden');
    }

    /**
     * Retrieve a cached value entry by id.
     *
     * @param string $id id of cache to entry
     * @param string $default default value to return if cache miss
     * @return  mixed
     * @throws  Exception
     */
    abstract public function get(string $id, string $default = NULL);

    /**
     * Set a value to cache with id and lifetime
     *
     * @param string $id id of cache entry
     * @param string $data data to set to cache
     * @param integer $lifetime lifetime in seconds
     * @return  boolean
     */
    abstract public function set(string $id, string $data, int $lifetime = 3600): bool;

    /**
     * Delete a cache entry based on id
     *
     * @param string $id id to remove from cache
     * @return  boolean
     */
    abstract public function delete(string $id): bool;

    /**
     * Delete all cache entries.
     *
     * Beware of using this method when
     * using shared memory cache systems, as it will wipe every
     * entry within the system for all clients.
     *
     * @return  boolean
     */
    abstract public function delete_all(): bool;

    /**
     * Replaces troublesome characters with underscores and adds prefix to avoid duplicates
     *
     * @param string $id id of cache to sanitize
     * @return  string
     */
    protected function _sanitize_id(string $id): string
    {
        // configuration for the specific cache group
        $prefix = $this->_config['prefix'] ?? Core::$config->load('cache.prefix');

        // sha1 the id makes sure name is not too long and has not any not allowed characters
        return $prefix . sha1($id);
    }
}
