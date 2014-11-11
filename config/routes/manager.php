<?php

use \app\adapters\AuthCheckMiddleware;

$router->add(new AuthCheckMiddleware());

$router->set("/manager/", function () use ($router) {
	$router->redirect("/manager/pages");
	//View::render("layouts/manager", array("title" => "Welcome to the Hub Manager."));
});

/**
 * Custom Login/Logout redirects
 */
$router->set("/manager(/users)/login(/)", function () use ($router) {
	$router->dispatch("users", "login");
});

$router->set("/manager/logout(/)", function () use ($router) {
	$router->dispatch("users", "logout");
});


/**
 * Alert management
 */
$router->get("/manager/alerts(/)", function () use ($router) {
	$router->dispatch("alerts", "index");
});

$router->post("/manager/alerts(/)", function () use ($router) {
	$router->dispatch("alerts", "create");
});

$router->post("manager/alerts/:id/:action(/)", function ($id, $action) use ($router) {
	$router->dispatch("alerts", $action, array("id" => $id));
});


/**
 * User management
 */

// users login, users register
$router->set("/manager/users/:action(/)", function ($action = null) use ($router) {
    $router->dispatch("users", $action);
});

// New user setup for the manager
$router->set("/newuser/setup", function () use ($router) {
	$router->dispatch("users", "setup");
});


/**
 * Instagram in manager
 */
$router->set("/manager/hubpix(/)", function () use ($router) {
	$router->dispatch("Hubstagram", "manage");
});



/**
 * Main MVC automation routes
 */
$router->set("/manager/:controller/create(/)", function ($controller) use ($router) {
    $router->dispatch($controller, "create");
});

$router->set("/manager/:controller(/)(/:id)(/:action(/))", function ($controller, $id = null, $action = null) use ($router) {
    $router->dispatch($controller, $action, array("id" => $id));
});
