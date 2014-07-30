<?php

// Hub articles
// Example: http://local.hub.jhu.edu/2013/05/23/jhu-commencement-live/gallery/1118/images/2185/_D3J7237.jpg
$router->set("/:year/:month/:day/:slug/gallery/:gid/images/:id/:filename", function ($year, $month, $day, $slug, $gid, $id, $filename) use ($router) {
	$redirect = "/{$year}/{$month}/{$day}/{$slug}#image={$filename}&id={$id}&gid={$gid}";
	socialImageHandling($id, $gid, $redirect, $router);
});

// Magazine articles
// Example: http://local.hub.jhu.edu/magazine/2013/summer/little-things/gallery/1203/images/2503/Microgrippers-F2.jpg
$router->set("/magazine/:year/:edition/:slug/gallery/:gid/images/:id/:filename", function ($year, $month, $day, $slug, $gid, $id, $filename) use ($router) {
	$redirect = "/magazine/{$year}/{$edition}/{$slug}#image={$filename}&id={$id}&gid={$gid}";
	socialImageHandling($id, $gid, $redirect, $router);
});

// Gazette articles
// Example: http://hub.jhu.edu/gazette/2013/june/features-commencement-shoes/gallery/1185/images/2437/SHOES_6827.jpg
$router->set("/gazette/:year/:edition/:slug/gallery/:gid/images/:id/:filename", function ($year, $month, $day, $slug, $gid, $id, $filename) use ($router) {
	$redirect = "/gazette/{$year}/{$edition}/{$slug}#image={$filename}&id={$id}&gid={$gid}";
	socialImageHandling($id, $gid, $redirect, $router);
});


function socialImageHandling($id, $gid, $redirect, $router)
{
	if (preg_match("/^([facebook|twitter].*)/i", $_SERVER["HTTP_USER_AGENT"])) {
		$router->dispatch("Images", "show", array("id" => $id, "gid" => $gid));

	} else {
		// Regular person
		$router->redirect($redirect, 301);
	}
}