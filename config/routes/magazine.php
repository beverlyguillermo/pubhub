<?php

$router->set("/magazine(/)(/:preview(/))", function ($preview = null) use ($router) {
	$options = array(
		"source" => "magazine",
		"preview" => checkPreview($preview)
	);
	$router->dispatch("Issues", "show", $options);
});

$router->set("/magazine/:year/:edition(/)(/:preview(/))", function ($year, $edition, $preview = null) use ($router) {
	$options = array(
		"source" => "magazine",
		"year" => $year,
		"edition" => $edition,
		"preview" => checkPreview($preview)
	);
	$router->dispatch("Issues", "show", $options);
});

$router->set("/magazine/:year/:edition/:slug(/)(/:preview(/))", function ($year, $edition, $slug, $preview = null) use ($router) {
	$options = array(
		"source" => "magazine",
		"year" => $year,
		"edition" => $edition,
		"slug" => $slug,
		"preview" => checkPreview($preview)
	);
	$router->dispatch("Articles", "show", $options);
});

$router->set("/magazine/:page(/)", function ($page) use ($router) {
	$options = array(
		"source" => "magazine",
		"page" => $page
	);
	$router->dispatch("Pages", "show", $options);
});