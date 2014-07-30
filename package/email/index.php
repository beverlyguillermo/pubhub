#!/usr/bin/php
<?php

namespace email;

date_default_timezone_set("America/New_York");
require dirname(__DIR__) . "/vendor/autoload.php";

$hubKey = "70a252dc26486819e5817371a48d6e3b5989cb2a";
$mailchimpKey = "9c9b808311102230a4d656563398016c";
$template = 65645;
$list = "84689ae133";

try {
	$http = new \HttpExchange\Adapters\Resty(new \Resty());


	// get data
	
	$announcementGetter = new data\AnnouncementGetter($http, $hubKey);
	$announcements = $announcementGetter->getHtml();
	$eventGetter = new data\EventGetter($http, $hubKey);
	$events = $eventGetter->getHtml();

	if (!$announcements && !$events) {
		die("No data. Email will not be sent.");
	}
	
	
	// send email

	$content = array(
		"sections" => array(
			"announcements" => $announcements . $events
		)
	);

	$options = array(
		"template_id" => $template,
		"list_id" => $list,
		"title" => "Today's Announcements for " . date("F j", strtotime("today")),
		"generate_text" => true,
		"subject" => "Today's Announcements for " . date("F j", strtotime("today"))
	);

	$api = new \MailChimp\Api($http, $mailchimpKey, "us5");
	$campaign = new \MailChimp\Campaign($api, $content, $options);
	$campaign->schedule("today 1am");

	echo "Email scheduled.";


} catch (\Exception $e) {

	$to = "jwachter@jhu.edu";

	if ($e instanceof \MailChimp\Exception\CreateCampaign) {
		echo "Failed to create campaign. Error thrown: {$e->getMessage()}\n";
		mail($to, "Today's Announcements email failed to be created", "Error thrown: {$e->getMessage()}", "From: hub@jhu.edu");
	
	} else if ($e instanceof \MailChimp\Exception\ScheduleCampaign) {
		echo "Failed to create campaign. Error thrown: {$e->getMessage()}\n";
		mail($to, "Today's Announcements email failed to be scheduled", "The campaign was created in MailChimp, but could not be scheduled. It should be visibile in MailChimp and can be scheduled manually. Error thrown: {$e->getMessage()}", "From: hub@jhu.edu");
	
	} else {
		throw($e);
	}
}
