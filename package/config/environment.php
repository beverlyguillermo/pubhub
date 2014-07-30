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

// if (!defined("ENVIRONMENT")) {
//     # First check the Apache environment
//     $env = getenv("APPLICATION_ENV");

//     # If it hasn't been set, getenv() will return false, so fall back
//     if (!$env) {
//         $env = "production";
        
//         $host = explode(".", $_SERVER["HTTP_HOST"]);
//         $key = $host[0];
//         if (array_key_exists($key, $subdomainMap)) {
//             $env = strtolower($subdomainMap[$key]);
//         }
//     }

//     define("ENVIRONMENT", $env);
// }

if (!defined("API_URL")) {
    if ($envSegment = array_search(ENVIRONMENT, $subdomainMap)) {
        $envSegment .= ".";
    } 
    // else {
    //     // This else statement should be REMOVED once we remove the "beta"
    //     // from our production URL
    //     $envSegment = "beta.";
    // }
    define("API_URL", "http://" . $envSegment . "api.hub.jhu.edu/");
}