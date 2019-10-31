<?php
/**
 * Base session class.
 *
 * @package    Modseven
 * @category   Session
 *
 * @copyright  (c) 2007-2016  Kohana Team
 * @copyright  (c) 2016-2019  Koseven Team
 * @copyright  (c) since 2019 Modseven Team
 * @license    https://koseven.ga/LICENSE
 */

namespace Modseven;

use Exception;

abstract class Session
{

    /**
     * @var  string  default session adapter
     */
    public static $default = 'native';

    /**
     * @var  array  session instances
     */
    public static $instances = [];
    /**
     * @var  string  cookie name
     */
    protected $_name = 'session';
    /**
     * @var  int  cookie lifetime
     */
    protected $_lifetime = 0;
    /**
     * @var  bool  encrypt session data?
     */
    protected $_encrypted = FALSE;
    /**
     * @var  array  session data
     */
    protected $_data = [];
    /**
     * @var  bool  session destroyed?
     */
    protected $_destroyed = false;

    /**
     * Overloads the name, lifetime, and encrypted session settings.
     *
     * [!!] Sessions can only be created using the [Session::instance] method.
     *
     * @param array $config configuration
     * @param string $id session id
     *
     * @throws Session\Exception
     */
    public function __construct(array $config = NULL, ?string $id = NULL)
    {
        if (isset($config['name'])) {
            // Cookie name to store the session id in
            $this->_name = (string)$config['name'];
        }

        if (isset($config['lifetime'])) {
            // Cookie lifetime
            $this->_lifetime = (int)$config['lifetime'];
        }

        if (isset($config['encrypted'])) {
            if ($config['encrypted'] === TRUE) {
                // Use the default Encrypt instance
                $config['encrypted'] = 'default';
            }

            // Enable or disable encryption of data
            $this->_encrypted = $config['encrypted'];
        }

        // Load the session
        $this->read($id);
    }

    /**
     * Loads existing session data.
     *
     * @param string $id session id
     * @return  void
     */
    public function read(?string $id = NULL): void
    {
        $data = NULL;

        try {
            if (is_string($data = $this->_read($id))) {
                if ($this->_encrypted) {
                    // Decrypt the data using the default key
                    $data = Encrypt::instance($this->_encrypted)->decode($data);
                } else {
                    // Decode the data
                    $data = $this->_decode($data);
                }

                // Unserialize the data
                $data = $this->_unserialize($data);
            }
        } catch (Exception $e) {
            // Error reading the session, usually a corrupt session.
            throw new Session\Exception('Error reading session data.', NULL, Session\Exception::SESSION_CORRUPT);
        }

        if (is_array($data)) {
            // Load the data locally
            $this->_data = $data;
        }
    }

    /**
     * Loads the raw session data string and returns it.
     *
     * @param string $id session id
     * @return  string
     */
    abstract protected function _read(?string $id = NULL): string;

    /**
     * Decodes the session data using [base64_decode].
     *
     * @param string $data data
     * @return  string
     */
    protected function _decode(string $data): string
    {
        return base64_decode($data);
    }

    /**
     * Unserializes the session data.
     *
     * @param string $data data
     * @return  array
     */
    protected function _unserialize(string $data): array
    {
        return unserialize($data, null);
    }

    /**
     * Creates a singleton session of the given type. Some session types
     * (native, database) also support restarting a session by passing a
     * session id as the second parameter.
     *
     * [!!] [Session::write] will automatically be called when the request ends.
     *
     * @param string $type type of session (native, cookie, etc)
     * @param string $id session identifier
     * @return  Session
     */
    public static function instance(?string $type = NULL, ?string $id = NULL): Session
    {
        if ($type === NULL) {
            // Use the default type
            $type = static::$default;
        }

        if (!isset(static::$instances[$type])) {
            // Load the configuration for this type
            $config = Core::$config->load('session')->get($type);

            // Set the session class name
            $class = 'Session_' . ucfirst($type);

            // Create a new session instance
            static::$instances[$type] = $session = new $class($config, $id);

            // Write the session at shutdown
            register_shutdown_function([$session, 'write']);
        }

        return static::$instances[$type];
    }

    /**
     * Session object is rendered to a serialized string. If encryption is
     * enabled, the session will be encrypted. If not, the output string will
     * be encoded.
     *
     * @return  string
     */
    public function __toString(): string
    {
        // Serialize the data array
        $data = $this->_serialize($this->_data);

        if ($this->_encrypted) {
            // Encrypt the data using the default key
            $data = Encrypt::instance($this->_encrypted)->encode($data);
        } else {
            // Encode the data
            $data = $this->_encode($data);
        }

        return $data;
    }

    /**
     * Serializes the session data.
     *
     * @param array $data data
     * @return  string
     */
    protected function _serialize(array $data): string
    {
        return serialize($data);
    }

    /**
     * Encodes the session data using [base64_encode].
     *
     * @param string $data data
     * @return  string
     */
    protected function _encode(string $data): string
    {
        return base64_encode($data);
    }

    /**
     * Returns the current session array. The returned array can also be
     * assigned by reference.
     *
     * @return  array
     */
    public function & as_array(): array
    {
        return $this->_data;
    }

    /**
     * Get the current session id, if the session supports it.
     *
     * [!!] Not all session types have ids.
     *
     * @return  string
     */
    public function id(): string
    {
        return NULL;
    }

    /**
     * Get the current session cookie name.
     *
     * @return  string
     */
    public function name(): string
    {
        return $this->_name;
    }

    /**
     * Get and delete a variable from the session array.
     *
     * @param string $key variable name
     * @param mixed $default default value to return
     * @return  mixed
     */
    public function get_once(string $key, $default = NULL)
    {
        $value = $this->get($key, $default);

        unset($this->_data[$key]);

        return $value;
    }

    /**
     * Get a variable from the session array.
     *
     * @param string $key variable name
     * @param mixed $default default value to return
     * @return  mixed
     */
    public function get(string $key, $default = NULL)
    {
        return array_key_exists($key, $this->_data) ? $this->_data[$key] : $default;
    }

    /**
     * Set a variable in the session array.
     *
     * @param string $key variable name
     * @param mixed $value value
     * @return  self
     */
    public function set(string $key, $value): self
    {
        $this->_data[$key] = $value;

        return $this;
    }

    /**
     * Set a variable by reference.
     *
     * @param string $key variable name
     * @param mixed $value referenced value
     * @return  self
     */
    public function bind(string $key, & $value): self
    {
        $this->_data[$key] =& $value;

        return $this;
    }

    /**
     * Removes a variable in the session array.
     *
     * @param string $key,... variable name
     * @return  self
     */
    public function delete(string $key): self
    {
        unset($this->_data[$key]);

        return $this;
    }

    /**
     * Generates a new session id and returns it.
     *
     * @return  string
     */
    public function regenerate(): string
    {
        return $this->_regenerate();
    }

    /**
     * Generate a new session id and return it.
     *
     * @return  string
     */
    abstract protected function _regenerate(): ?string;

    /**
     * Sets the last_active timestamp and saves the session.
     *
     * [!!] Any errors that occur during session writing will be logged,
     * but not displayed, because sessions are written after output has
     * been sent.
     *
     * @return  boolean
     */
    public function write(): bool
    {
        if (headers_sent() || $this->_destroyed) {
            // Session cannot be written when the headers are sent or when
            // the session has been destroyed
            return FALSE;
        }

        // Set the last active timestamp
        $this->_data['last_active'] = time();

        try {
            return $this->_write();
        } catch (Exception $e) {
            // Log & ignore all errors when a write fails
            Core::$log->error(\Modseven\Exception::text($e));
            return FALSE;
        }
    }

    /**
     * Writes the current session.
     *
     * @return  boolean
     */
    abstract protected function _write(): bool;

    /**
     * Restart the session.
     *
     * @return  boolean
     */
    public function restart(): bool
    {
        if ($this->_destroyed === FALSE) {
            // Wipe out the current session.
            $this->destroy();
        }

        // Allow the new session to be saved
        $this->_destroyed = FALSE;

        return $this->_restart();
    }

    /**
     * Completely destroy the current session.
     *
     * @return  boolean
     */
    public function destroy(): bool
    {
        if (($this->_destroyed === false) && $this->_destroyed = $this->_destroy()) {
            // The session has been destroyed, clear all data
            $this->_data = [];
        }

        return $this->_destroyed;
    }

    /**
     * Destroys the current session.
     *
     * @return  boolean
     */
    abstract protected function _destroy(): bool;

    /**
     * Restarts the current session.
     *
     * @return  boolean
     */
    abstract protected function _restart(): bool;

}
