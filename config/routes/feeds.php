<?php

/**
 * /today route used for today's announcements email
 */
$router->set("/events/feed(/:today)(/)", function ($today = null) use ($router) {
	$options = array(
		"type" => "event",
		"today" => $today ? true : false
	);
	$router->dispatch("Feeds", "show", $options);
});

/**
 * /today route used for today's announcements email
 */
$router->set("/announcements/feed(/:today)(/)", function ($today = null) use ($router) {
	$options = array(
		"type" => "announcement",
		"today" => $today ? true : false
	);
	$router->dispatch("Feeds", "show", $options);
});

$router->set("/media/feed(/)", function () use ($router) {
	$options = array(
		"type" => "media"
	);
	$router->dispatch("Feeds", "show", $options);
});

$router->set("/:publication/feed(/)", function ($publication = null) use ($router) {
	$publications = array("magazine", "gazette");
	if (!in_array($publication, $publications)) $router->pass();

	$options = array(
		"type" => "publication",
		"publication" => strtolower($publication)
	);
	$router->dispatch("Feeds", "show", $options);
});

$router->set("/:topic/feed(/)", function ($topic = null) use ($router) {
	$options = array(
		"type" => "topic",
		"topic" => strtolower($topic)
	);
	$router->dispatch("Feeds", "show", $options);
});

$router->set("/tags/:tag/feed(/)", function ($tag = null) use ($router) {
	$options = array(
		"type" => "tag",
		"tag" => strtolower($tag)
	);
	$router->dispatch("Feeds", "show", $options);
});

$router->set("/feed(/)", function ($topic = null) use ($router) {
	$options = array(
		"topic" => strtolower($topic)
	);
	$router->dispatch("Feeds", "show", $options);
});