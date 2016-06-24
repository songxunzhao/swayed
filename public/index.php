<?php
use Illuminate\Database\Capsule\Manager as Capsule;

if (PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $file = __DIR__ . $_SERVER['REQUEST_URI'];
    if (is_file($file)) {
        return false;
    }
}

require __DIR__ . '/../vendor/autoload.php';

session_start();

// Instantiate the app
$settings = require __DIR__ . '/../src/settings.php';
$app = new \Slim\App($settings);

// Set up dependencies
require __DIR__ . '/../src/dependencies.php';

// Register middleware
require __DIR__ . '/../src/middleware.php';

// Register routes
require __DIR__ . '/../src/routes.php';

//load model
foreach (scandir((__DIR__ . '/../app/models/')) as $filename) {
    $path = (__DIR__ . '/../app/models') . '/' . $filename;
    if (is_file($path)) {
        require $path;
    }
}

define("UPLOAD_PATH", __DIR__ . '/upload');
define("BASEURL", "http://api.swayedserv.com/swayed/public");

// Database information
$settings = array(
    'driver' => 'mysql',
    'host' => '127.0.0.1',
    'database' => 'webdev_swayed',
    'username' => 'webdev_swayed',
    'password' => '$?wH,uFEq7sK',
    'collation' => 'utf8_general_ci',
    'prefix' => '',
    'charset'   => 'utf8',
);
$capsule = new Capsule;
$capsule->addConnection($settings);
$capsule->bootEloquent();
$capsule->setAsGlobal();

// Run app
$app->run();
