<?php

use \app\workers\HTTP;
use \app\workers\GoogleSearchResult;
use \app\base\View;

require "../liftoff.php";

$results = array();

if (isset($_GET["q"])) {
	$http = new HTTP(new Resty());
	$check = $http->setBaseUrl("http://search.johnshopkins.edu/");
	// var_dump($check);
	$http->setEndpoint("search")
		->addQueryStringParam("q", $_GET["q"]);
	
	$response = $http->get();
	$xml = $response["body"];

	$results = new GoogleSearchResult($xml);
	var_dump($results);
}

View::render("pages/hub/search", $results);
?>

