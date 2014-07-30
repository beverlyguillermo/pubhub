<?php

namespace app\models\feeds;

class Topic extends \app\models\Feeds
{
	public function __construct($options)
    {
    	parent::__construct($options);
    }

	/**
     * Figure out if we're supposed to create a
     * Hub feed (createFeed()) or compile the JHU feed.
     * @return null
     */
	public function getFeed()
	{
		$data = $this->getData();
		$this->createFeed($data);
	}

    /**
	 * Get the data needed to create a Hub feed
	 * @return array
	 */
	protected function getData($count = 20)
	{
		$title = "Hub";
		$description = "Headlines from the Johns Hopkins news network";
		$image = array(
			"url" => $this->getBaseUrl() . "/assets/img/hub-logo-rss.png",
			"title" => "Hub",
			"link" => $this->getBaseUrl() . ""
		);
		$link = $this->getBaseUrl() . "/{$this->topic}";

		$endpoint = $this->getEndpoint($this->topic);
		$response = $this->http->setEndpoint($endpoint)
					->addQueryStringParam("per_page", $count)
					->get();

		return $response->_embedded->articles;
	}

	protected function createChannel()
	{
		$channel = parent::createChannel();
		$channel["link"] = $this->getBaseUrl() ."/{$this->topic}";

		return $channel;
	}

    protected function getEndpoint($slug)
    {
    	$this->tableName = "pages";
    	$page_data = parent::findByField(array("slug" => $slug));

    	if (empty($page_data)) {
    		$this->router->notFound();
    	}

    	return $page_data[0]["endpoint"];
    }
}