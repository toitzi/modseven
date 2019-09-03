<?php

// The directory in which your application specific resources are located.
$application = 'application';

// The directory in which your modules are located.
$modules = 'modules';

// The directory in which the Koseven core resources are located.
$system = 'system';

// The directory in which the Koseven public files are located.
$public = 'public';

// Set the full path to the docroot
define('DOCROOT', dirname(__DIR__) . DIRECTORY_SEPARATOR);

// Make the application relative to the docroot, for symlink'd index.php
if (!is_dir($application) && is_dir(DOCROOT . $application)) {
    $application = DOCROOT . $application;
}

// Make the modules relative to the docroot, for symlink'd index.php
if (!is_dir($modules) && is_dir(DOCROOT . $modules)) {
    $modules = DOCROOT . $modules;
}

// Make the system relative to the docroot, for symlink'd index.php
if (!is_dir($system) && is_dir(DOCROOT . $system)) {
    $system = DOCROOT . $system;
}

// Make the public relative to the docroot, for symlink'd index.php
if (!is_dir($public) && is_dir(DOCROOT . $public)) {
    $public = DOCROOT . $public;
}

// Define the absolute paths for configured directories
define('APPPATH', realpath($application) . DIRECTORY_SEPARATOR);
define('MODPATH', realpath($modules) . DIRECTORY_SEPARATOR);
define('SYSPATH', realpath($system) . DIRECTORY_SEPARATOR);
define('PUBPATH', realpath($public) . DIRECTORY_SEPARATOR);

// Clean up the configuration vars
unset($application, $modules, $system, $public);

// Load the installation check
if (file_exists('install.php')) {
    return include 'install.php';
}

// Define the start time of the application, used for profiling.
if (!defined('KO7_START_TIME')) {
    define('KO7_START_TIME', microtime(true));
}

// Define the memory usage at the start of the application, used for profiling.
if (!defined('KO7_START_MEMORY')) {
    define('KO7_START_MEMORY', memory_get_usage());
}

///////////////////////////////////////////////////////////////////
// ------------ Start Bootstrapping the Application ------------ //
///////////////////////////////////////////////////////////////////

// Load the core KO7 class
// Check if composer has been initialized
if (!is_file(DOCROOT . '/vendor/autoload.php')) {
    die('RuntimeError: Please run `composer install` inside your project root.');
}

// Require composer autoloader
\KO7\Core::$autoloader = require DOCROOT . '/vendor/autoload.php';

// Enable autoloader for unserialization
ini_set('unserialize_callback_func', 'spl_autoload_call');

// Attach Configuration Reader and Load App Configuration
$config = new \KO7\Config;
$config->attach(new \KO7\Config\File);
try {
    $conf = $config->load('app')->as_array();
} catch (\KO7\Exception $e) {
    die('RuntimeError: Could not initialize Configuration ' . $e->getMessage());
}

// Set the PHP error reporting level.
if ($conf['reporting']) {
    error_reporting($conf['reporting']);
}

// Set Timezone
date_default_timezone_set($conf['timezone']);

// Set Locale
setlocale(LC_ALL, $conf['locale']);

// Set the mb_substitute_character to "none"
mb_substitute_character('none');

// Set default language
$lang = strpos($conf['locale'], '.') !== false ? substr($conf['locale'], 0, strpos($conf['locale'], '.')) : $conf['locale'];
\KO7\I18n::lang(str_replace('_', '-', strtolower($lang)));

// Replace the default protocol.
if (isset($_SERVER['SERVER_PROTOCOL'])) {
    \KO7\HTTP::$protocol = $_SERVER['SERVER_PROTOCOL'];
}

// Set KO7::$environment if a 'KOSEVEN_ENV' environment variable has been supplied.
if (isset($_SERVER['KOSEVEN_ENV'])) {
    \KO7\Core::$environment = constant('KO7::' . strtoupper($_SERVER['KOSEVEN_ENV']));
}

// Set the current configuration class
\KO7\Core::$config = $config;

// Initialize KO7, setting the default options.
\KO7\Core::init([
    'base_url' => $conf['base_url'],
    'index_file' => $conf['index_file'],
    'charset' => $conf['charset'],
    'errors' => $conf['errors'],
    'profile' => $conf['profile'],
    'caching' => $conf['caching'],
    'expose' => $conf['expose']
]);

// Attach a new file writer to logging.
\KO7\Core::$log->attach(new \KO7\Log\File(APPPATH . 'logs'));

// Initialize Modules
\KO7\Core::modules($conf['modules']);

// Cookie Salt
\KO7\Cookie::$salt = $conf['cookie']['salt'];

// Cookie HttpOnly directive
\KO7\Cookie::$httponly = $conf['cookie']['httponly'];

// If website runs on secure protocol HTTPS, allows cookies only to be transmitted via HTTPS.
if ($conf['cookie']['secure']) {
    \KO7\Cookie::$secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
}

// Set the application name before initializing routes and add it to composer autoloader
\KO7\Core::$app_ns = $conf['name'];
\KO7\Core::register_module($conf['name'] . '\\', APPPATH . DIRECTORY_SEPARATOR . 'classes');

// Bootstrap the application
require APPPATH . 'routes.php';

if (PHP_SAPI === 'cli') {
    // Try and load minion
    class_exists('\Minion\Task') OR die('Please enable the Minion module for CLI support.');
    set_exception_handler(['\Minion\Exception', 'handler']);

    \Minion\Task::factory(\Minion\CLI::options())->execute();
} else {
    /**
     * Execute the main request. A source of the URI can be passed, eg: $_SERVER['PATH_INFO'].
     * If no source is specified, the URI will be automatically detected.
     */
    echo \KO7\Request::factory(true, [], false)
        ->execute()
        ->send_headers(true)
        ->body();
}