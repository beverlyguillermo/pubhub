<?php

namespace app\models;
use \app\workers\HTTP;
use \app\workers\Cache;
use \Resty;

class Feeds extends Model
{
	protected $tableName = "feeds";

	public $data = array();
	protected $response;

	/**
	 * /today feed
	 * @var boolean
	 */
	protected $today = false;

    public function __construct($options)
    {
    	parent::__construct($options);
    	$this->cache = new Cache();
    }

    protected function getBaseUrl()
    {
    	if (!defined("ENVIRONMENT")) {
    		return "http://hub.jhu.edu";
    	}

    	if (ENVIRONMENT == "development") {
    		return "http://local.hub.jhu.edu";
    	} elseif  (ENVIRONMENT == "staging") {
    		return "http://staging.hub.jhu.edu";
    	} else {
    		return "http://hub.jhu.edu";
    	}
    }

	public function getFeed()
	{
		$data = $this->getData();
		$data = $this->filterData($data);
		$this->createFeed($data);
	}

	/**
	 * Get the data from the API
	 * @return array
	 */
	protected function getData($count = 20)
	{
		$response = $this->http->setEndpoint("articles")
				->addQueryStringParam("per_page", $count)
				->get();

		return $response->_embedded->articles;
	}

	/**
	 * Run the data through any necessary filters
	 * before encoding it.
	 * @param  array $data Data from API
	 * @return array Filtered data
	 */
	protected function filterData($data)
	{
		return $data;
	}

	protected function getDataMap()
	{
		return array(
			"headline" => "title",
			"body" => "description",
			"url" => "link",
			"publish_date" => "pubDate"
		);
	}

	protected function createFeed($data)
	{
		$channel = $this->createChannel();

		$dataMap = $this->getDataMap();

		$itemElements = array_values($dataMap);

		$rss = new \DataEncoder\RSS($channel, $itemElements, $data, $dataMap);
		$rss->render();
	}

	/**
	 * Create the elements included in the channel element
	 * @return array
	 */
	protected function createChannel()
	{
		return array(
			"title" => "Hub", 
			"link" => $this->getBaseUrl(),
			"description" => "Headlines from the Johns Hopkins news network",
			"image" => array(
				"url" => $this->getBaseUrl() . "/assets/img/hub-logo-rss.png",
				"title" => "Hub",
				"link" => $this->getBaseUrl()
			)
		);
	}
    
}