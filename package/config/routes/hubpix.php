<?php

$instagramClientId = "ca91fd6dc6104157867fa6b55e62e6df";
$instagramAccessToken = "268026917.ca91fd6.fd5506f89f98418f984cf706eb239678";


// Get Instagram photos to promote on article sidebars
$router->set("/instagram/api(/)", function() use ($router, $instagramClientId, $instagramAccessToken) {

	$options = $router->request()->params();

	$httpEngine = new \HttpExchange\Adapters\Resty(new \Resty());
    $datastore = new \CacheExchange\Adapters\APC();
    $cache = new \CacheExchange\Cache($datastore);
    
    $options["instahook"] = new \InstaHook\Client($httpEngine, $cache, $instagramClientId, $instagramAccessToken);

	$router->dispatch("Hubstagram", "promote", $options);
});

// HubPix page
$router->set("/pix(/)", function() use ($router, $instagramClientId, $instagramAccessToken) {
	
	$options = array();

	// Cronjob uses the updateCache parameter to force the hubpix cache to clear
	$updateCache = $router->request()->params("updateCache");
	if (isset($updateCache)) {
		$options["updateCache"] = true;
	} else {
		$options["updateCache"] = false;
	}

	$httpEngine = new \HttpExchange\Adapters\Resty(new \Resty());
    $datastore = new \CacheExchange\Adapters\APC();
    $cache = new \CacheExchange\Cache($datastore);
    $options["instahook"] = new \InstaHook\Client($httpEngine, $cache, $instagramClientId, $instagramAccessToken);

	$router->dispatch("Hubstagram", "show", $options);
});