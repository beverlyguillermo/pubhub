<?php

$router->set("/gazette(/)(/:preview(/))", function ($preview = null) use ($router) {
	$options = array(
		"source" => "gazette",
		"preview" => checkPreview($preview)
	);
	$router->dispatch("Issues", "show", $options);
});

$router->set("/gazette/:year/:edition(/)(/:preview(/))", function ($year, $edition, $preview = null) use ($router) {
	$options = array(
		"source" => "gazette",
		"year" => $year,
		"edition" => $edition,
		"preview" => checkPreview($preview)
	);
	$router->dispatch("Issues", "show", $options);
});

$router->set("/gazette/:year/:edition/:slug(/)(/:preview(/))", function ($year, $edition, $slug, $preview = null) use ($router) {
	$options = array(
		"source" => "gazette",
		"year" => $year,
		"edition" => $edition,
		"slug" => $slug,
		"preview" => checkPreview($preview)
	);
	$router->dispatch("Articles", "show", $options);
});

$router->set("/gazette/:page(/)", function ($page) use ($router) {
	$options = array(
		"source" => "gazette",
		"page" => $page
	);
	$router->dispatch("Pages", "show", $options);
});