<?php

namespace app\adapters;
use \app\workers\Auth;
use \app\workers\Messages;
use \app\workers\Router;

class AuthCheckMiddleware extends \Slim\Middleware
{
	public function call()
	{
		$router = Router::getInstance();
		$matchedRoute = $router->request()->getResourceUri();
		$segments = explode("/", $matchedRoute);

		if ($matchedRoute != "/manager/login" && $segments[1] === "manager") {
			Auth::check();
		}

		// Rudimentary way of blocking access to certain areas of the manager
		// How do we set this up in a more robust way?
		$currentUser = Auth::loggedInUser();
		if ($currentUser && (count($segments) > 2) && $segments[2] === "users" && $currentUser->role !== "admin") {
			Messages::push("error", "You're not allowed to see that page ({$matchedRoute})!");
			$router->redirect("/manager");
		}
		
		$this->next->call();
	}
}