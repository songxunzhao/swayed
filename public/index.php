<?php
use Illuminate\Database\Capsule\Manager as Capsule;
use App\Loader\Config;
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
$settings = require __DIR__ . '/../src/settings.php';;
$app = new \Slim\App($settings);

// Set up dependencies
require __DIR__ . '/../src/dependencies.php';

// Register middleware
require __DIR__ . '/../src/middleware.php';

// Register routes
require __DIR__ . '/../src/routes.php';

define("UPLOAD_PATH", __DIR__ . '/upload');
define("BASEURL", "http://api.swayedserv.com/swayed/public");

// Database information
$db_settings = Config::loadConfig('database');
$capsule = new Capsule;
$capsule->addConnection($db_settings);
$capsule->bootEloquent();
$capsule->setAsGlobal();

// Run app
$app->run();
