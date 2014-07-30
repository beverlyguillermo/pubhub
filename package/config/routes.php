<?php
/**
 * @file
 * Routes file
 *
 * You can pass a different router file to each constructed Router
 * object to have different sets of routes in different files.
 *
 * $router is set to the Router object, and includes an overloaded __call
 * method that lets you call directly to the engine (Slim, etc)
 *
 */

use \app\workers\Router;


/**
 * Router instance
 * @var Object
 */
$router = Router::getInstance();


/**
 * Analyze the text that was passed into the `preview`
 * variable of a route to determine if the route is
 * in preview mode.
 * @param  string $preview Text
 * @return boolean TRUE if in preview mode; FALSE if not.
 */
function checkPreview($preview)
{
	if (!is_null($preview)) {
		
		// enable preview on this route
		if ($preview == "preview") {
			return true;

		// something was entered into the preview variable slot that
		// isn't "preview" -- try and find another route
		} else {
			$router = Router::getInstance();
			$router->pass();
		}

	// no preview on this route
	} else {
		return false;
	}
}



// Not found handler
$router->notFound(function () use ($router) {
	$options = array(
		"source" => "hub",
		"page" => "not-found"
	);
	$router->dispatch("Pages", "show", $options);
});

// Hub homepage
$router->set("/", function () use ($router) {	
	$options = array(
		"source" => "hub",
		"page" => "/"
	);
	$router->dispatch("Pages", "show", $options);
});


// Get Tweets
$router->set("/twitter/api(/)", function() use ($router) {
	$router->dispatch("Tweets", "call", $router->request()->params());
});




// Manager pages
include "routes/manager.php";

// Search page
include "routes/search.php";

// All feeds
include "routes/feeds.php";

// Magazine home, issue, article, and static pages
include "routes/magazine.php";

// Gazette home, issue, article, and static pages
include "routes/gazette.php";

// Taxonomy and article pages
include "routes/hub.php";

// HubPix page and sidebar promo API route
include "routes/hubpix.php";

// Image pages for each gallery image for Facebook/Twitter
include "routes/galleries.php";


// Static pages
$router->set("/:page(/)(/:subpages+(/))", function($page, $subpages = array()) use ($router) {
	$options = array(
		"source" => "hub",
		"page" => $page,
		"subpages" => $subpages
	);
	$router->dispatch("Pages", "show", $options);
});

$router->run();