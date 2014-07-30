<?php

/**
 * Set up the environment looking for an Apache SetEnv variable first,
 * but falling back to a lookup of HTTP_HOST
 */

$subdomainMap = array(
    "local" => "development", 
    "staging" => "staging" 
);

use \app\workers\Environment;

Environment::define(array(
    "local" => "development", 
    "staging" => "staging" 
));

if (!defined("API_URL")) {
    define("API_URL", "http://api.hub.jhu.edu/");
}
