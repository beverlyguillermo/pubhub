<?php

use \app\base\View;
use \app\workers\HTTP;
use \app\workers\GoogleSearchResult;

$router->set("/search", function () use ($router) {
	$results = array();
	$perPage = 10;
	$paginationInfo = array();

	if (isset($_GET["q"])) {
		$page = (isset($_GET["page"]) && $_GET["page"] > 1) ? $_GET["page"] : 1;

		$http = new HTTP(new Resty());
		$http->setBaseUrl("http://search.johnshopkins.edu/");
		
		$http->setEndpoint("search")
			->addQueryStringParam("q", $_GET["q"])
			->addQueryStringParam("site", "hub")
			->addQueryStringParam("client", "hub_frontend")
			->addQueryStringParam("output", "xml_no_dtd");

		if ($page > 1) {
			$offset = ($_GET["page"] - 1) * $perPage;
			$http->addQueryStringParam("start", $offset);
		}
		
		$response = $http->get();

		if (is_string($response)) {
			$response = simplexml_load_string($response);
		}

		$results = new GoogleSearchResult($response);

		if (!isset($results->results)) {
			$results->results = 0;
		}

		else {

			// Send information about pagination into the view
			// NOTE: Outside of Twig, we have to access $results->results
			$totalCount = $results->results->rCount;

			$paginationInfo["pagination"] = array (
				"pageCount" => ceil($totalCount / $perPage),
				"hasPrevious" => $page > 1,
				"hasNext" => (($page * $perPage) < $totalCount),
				"currentPage" => $page
			);

			// Create a page link array with 5 values, with the current page in the center (if possible)
			$start = $page - 2;
			if ($start < 1) { $start = $page - 1; }
			if ($start < 1 ) { $start = $page; }

			$paginationInfo["pagination"]["pageLinks"] = array_keys(array_fill($start, 5, "filler"));

		}

	}

	View::render("pages/hub/search", array("page_title" => "Search") + (array) $paginationInfo + (array) $results);
});