<?php

namespace app\models\feeds;

class Publication extends \app\models\Feeds
{
	public function __construct($options)
    {
    	parent::__construct($options);
    }

	protected function getData($count = -1)
	{
		$issue = $this->getLatestIssue();

		$response = $this->http->clearPrevious()
			->setEndpoint($this->extractLinkFromObject($issue->_links->articles))
			->addQueryStringParam("per_page", $count)
			->get();

		return $response->_embedded->articles;
	}

	protected function createChannel()
	{
		if ($this->publication == "magazine") {
			return array(
				"title" => "Johns Hopkine Magazine", 
				"link" => $this->getBaseUrl() . "/magazine",
				"description" => "The latest from Johns Hopkins Magazine.",
				"image" => array(
					"url" => $this->getBaseUrl() . "/assets/img/magazine/masthead.gif",
					"title" => "Johns Hopkine Magazine",
					"link" => $this->getBaseUrl() . "/magazine"
				)
			);

		} elseif ($this->publication == "gazette") {
			return array(
				"title" => "Gazette", 
				"link" => $this->getBaseUrl() . "/gazette",
				"description" => "The latest from the Gazette.",
				"image" => array(
					"url" => $this->getBaseUrl() . "/assets/img/gazette/masthead.png",
					"title" => "Gazette",
					"link" => $this->getBaseUrl() . "/gazette"
				)
			);
		}
	}

	/**
	 * Get the latest publication issue
	 * @return array
	 */
	protected function getLatestIssue()
	{
		$response = $this->http->clearPrevious()
			->setBaseURL(API_URL)
			->setEndpoint("issues")
			->addQueryStringParam("source", $this->publication)
			->addQueryStringParam("per_page", 1)
			->addQueryStringParam("page", 1)
			->addQueryStringParam("v", "0")
			->addQueryStringParam("key", "70a252dc26486819e5817371a48d6e3b5989cb2a")
			->get();

		return array_shift($response->_embedded->issues);
	}
}