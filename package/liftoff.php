<?php

/**
 * Similar to the bootstrap file found in other frameworks,
 * this file is the only one you should have to directly load.
 */

define("APP_DIR", __DIR__);
define("VIEWS_DIR", __DIR__ . "/app/views");
date_default_timezone_set("America/New_York");



/**
 * Autoload
 */
require __DIR__ . "/vendor/autoload.php";



/**
 * Environment setup based on Apache SetEnv variable first, with a HTTP_HOST fallback
 * 
 */
require __DIR__ . "/config/environment.php";



/**
 * Debugging based on environment
 */
if (defined("ENVIRONMENT") && ENVIRONMENT != "production") {
	define("DEBUG", true);
} else {
	define("DEBUG", false);
}



/**
 * Exception Handling
 *
 * Slim seems to handle this for us already, but we may not always use
 * the Slim framework, and exception handling should be done directly
 */
$exceptionHandler = new \app\workers\ExceptionHandler;


/**
 * Detailed Logging
 *
 */

require __DIR__ . "/config/logs.php";


/**
 * Database Connections
 *
 * Add connection information to databases in your config/database.php file
 */
require __DIR__ . "/config/database.php";


/**
 * View template engine set up
 * 
 * (Hooray for dependency injection, I think?)
 */
$twig = new \app\adapters\Twig(VIEWS_DIR, array( "autoescape" => false));

\app\base\View::setEngine($twig);


/**
 * Routing
 *
 */
use \app\workers\Router;

Router::init("default", array(
	"engine" => new \Slim\Slim(array(
	    "debug" => DEBUG
	)),
	"debug" => DEBUG
));
$router = Router::getInstance("default");
$router->start();