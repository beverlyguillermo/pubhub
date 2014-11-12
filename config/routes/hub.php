<?php

// Taxonomy pages
$router->set("/:vocab/:term(/:page)(/)", function ($vocab, $term, $page = 1) use ($router) {
	$allowable_vocabs = array("tags", "channels", "departments", "divisions", "topics");
	if (!in_array($vocab, $allowable_vocabs)) $router->pass();

	$options = array(
		"page" => "taxonomies",
		"vocabulary" => $vocab,
		"term" => $term,
		"page_number" => $page
	);

	$router->dispatch("Pages", "show", $options);
});


// Hub articles
$router->set("/:year/:month/:day/:slug(/)(/:preview(/))", function ($year, $month, $day, $slug, $preview = null) use ($router) {

	// Convert one-digit days to two-digits. We need this check because when the Hub rolled out
	// v1, we were constructing days in the URL with one digit. In hotfix-1.0.1, we changed
	// the createURL() method to construct URLs with two-digit dates.

	if (strlen($day) < 2 && $day < 10) {
		$add = "";
		if (checkPreview($preview)) {
			$add = "/preview";
		}
		$router->redirect("/{$year}/{$month}/0{$day}/{$slug}{$add}");
	}

	$options = array(
		"source" => "hub",
		"year" => $year,
		"month" => $month,
		"day" => $day,
		"slug" => $slug,
		"preview" => checkPreview($preview)
	);
	$router->dispatch("Articles", "show", $options);
});


// Events
$router->set("/event/:year/:month/:day/:slug(/)(/:preview(/))(/:subscribe(/))", function ($year, $month, $day, $slug, $preview = null, $subscribe = null) use ($router) {
	if (checkSubscribe($subscribe)) {
		$router->redirect("/events_subscribe/{$year}/{$month}/{$day}/{$slug}");
	} else {
	// redirect /event/... to /events/...
	$preview = checkPreview($preview) ? "/preview" : "";
	$router->redirect("/events/{$year}/{$month}/{$day}/{$slug}{$preview}");
	}
});

$router->set("/events/:year/:month/:day/:slug(/)(/:preview(/))(/:subscribe(/))", function ($year, $month, $day, $slug, $preview = null, $subscribe = null) use ($router) {
	if (checkSubscribe($subscribe)) {
		$router->redirect("/events_subscribe/{$year}/{$month}/{$day}/{$slug}");
	} else {
	$options = array(
		"year" => $year,
		"month" => $month,
		"day" => $day,
		"slug" => $slug,
		"preview" => checkPreview($preview)
	);
	$router->dispatch("Events", "show", $options);
	}
});

$router->set("/events_subscribe(/:year/)(:month/)(:day/)(:slug(/))", function ($year = null, $month = null, $day = null, $slug = null) use ($router) {
	$options = array(
		"year" => $year,
		"month" => $month,
		"day" => $day,
		"slug" => $slug,
		"preview" => false
	);
	$router->dispatch("Events", "subscribe", $options);
});

// Announcements
$router->set("/announcements(/)", function () use ($router) {
	$router->redirect("/");
});

$router->set("/announcements/:year/:month/:day/:slug(/)(/:preview(/))", function ($year, $month, $day, $slug, $preview = null) use ($router) {
	$options = array(
		"year" => $year,
		"month" => $month,
		"day" => $day,
		"slug" => $slug,
		"preview" => checkPreview($preview)
	);
	$router->dispatch("Announcements", "show", $options);
});