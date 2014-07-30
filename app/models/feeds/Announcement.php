<?php

namespace app\models\feeds;

class Announcement extends \app\models\Feeds
{
	public function __construct($options)
    {
    	parent::__construct($options);
    }


	protected function getData($count = 20)
	{
		$title = "Hub";
		$description = "Announcements from the Johns Hopkins news network";
		$image = array(
			"url" => $this->getBaseUrl() . "/assets/img/hub-logo-rss.png",
			"title" => "Hub",
			"link" => $this->getBaseUrl() . ""
		);
		$link = $this->getBaseUrl();

		$this->http->setEndpoint("announcements")
			->addQueryStringParam("per_page", $count);

		if ($this->today) {
			$this->http->addQueryStringParam("publish_date", date("Y-m-d", strtotime("yesterday")));
		}

		$response = $this->http->get();
		return $response->_embedded->announcements;
	}

	protected function createChannel()
	{
		$channel = parent::createChannel();
		$channel["link"] = $this->getBaseUrl();

		return $channel;
	}
}