<?php
// session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');  // Your database username
define('DB_PASS', '');      // Your database password
define('DB_NAME', 'parliament_system');

// Include Database class
require_once __DIR__ . '/Database.php';

// Autoloader
spl_autoload_register(function ($class) {
    $paths = ['models/', 'repositories/', 'controllers/'];
    foreach ($paths as $path) {
        $file = __DIR__ . "/$path$class.php";
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});