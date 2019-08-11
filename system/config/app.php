<?php

return [
    'name' => 'Application',
    'base_url' => '/',
    'index_file' => 'index.php',
    'charset' => 'utf-8',
    'errors' => TRUE,
    'reporting' => E_ALL,
    'profile' => FALSE,
    'caching' => FALSE,
    'expose' => FALSE,
    'timezone' => 'Europe/Vienna',
    'locale' => 'en_US.utf-8',
    'cookie' => [
        'salt' => FALSE,
        'httponly' => FALSE,
        'secure' => FALSE,
    ],
    'modules' => [
        // 'auth'       => MODPATH.'auth',       // Basic authentication
        // 'cache'      => MODPATH.'cache',      // Caching with multiple backends
        // 'codebench'  => MODPATH.'codebench',  // Benchmarking tool
        // 'database'   => MODPATH.'database',   // Database access
        // 'encrypt'    => MODPATH.'encrypt',    // Encryption support
        // 'image'      => MODPATH.'image',      // Image manipulation
        // 'minion'     => MODPATH.'minion',     // CLI Tasks
        // 'orm'        => MODPATH.'orm',        // Object Relationship Mapping
        // 'pagination' => MODPATH.'pagination', // Pagination
        // 'unittest'   => MODPATH.'unittest',   // Unit testing
        // 'userguide'  => MODPATH.'userguide',  // User guide and API documentation
    ]
];
