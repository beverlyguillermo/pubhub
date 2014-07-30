<?php

namespace email\data;

abstract class SectionGetter
{
	/**
	 * Instance of \HttpExchange\Interfaces\ClientInterface
	 * @var object
	 */
	protected $http;

	/**
	 * Hub API key
	 * @var string
	 */
	protected $key;

	/**
	 * Hub API base URL
	 * @var string
	 */
	protected $baseUrl;

	/**
	 * Title of section the is being compiled.
	 * Set by subclasses
	 * @var string
	 */
	protected $title;

	/**
	 * __construct
	 * @param object $http Instance of \HttpExchange\Interfaces\ClientInterface
	 * @param string $key Hub API key
	 */
	public function __construct(\HttpExchange\Interfaces\ClientInterface $http, $key)
	{
		$this->http = $http;
		$this->key = $key;
		$this->constructBaseUrl();
	}

	/**
	 * Is there way to find out which server we're on
	 * when running a cron job?
	 */
	protected function constructBaseUrl()
	{
		$this->baseUrl = "http://api.hub.jhu.edu/";
	}
	
	/**
	 * Generate and return HTML for this section
	 * @return string
	 */
	public function getHtml()
	{
		$data = $this->getData();

		if (empty($data)) {
			return null;
		}

		return $this->getOpening() . $this->getHeader() . $this->getItems($data) . $this->getClosing();
	}

	/**
	 * Retrieve data from the Hub API
	 * @return array
	 */
	protected function getData() { }

	/**
	 * Make a GET request to the Hub API
	 * @param  string $endpoint API endpoint
	 * @param  array $data Data to send to the request. API key is added automatically.
	 * @return array
	 */
	public function get($endpoint, $data)
	{
		$response = $this->http->get($this->baseUrl . $endpoint, $this->addKey($data))->getBody();

		if (property_exists($response, "error")) {
			throw new \Exception($response->message, $response->statusCode);
		}

		return $response->_embedded->$endpoint;
	}

	/**
	 * Adds the API key to the data array
	 * @param array $data
	 * @return array
	 */
	protected function addKey($data = array())
	{
		$data["key"] = $this->key;
		$data["v"] = 0;
		return $data;
	}

	/**
	 * Get opening HTML for this section.
	 * @return string
	 */
	protected function getOpening()
	{
		return "<div style='padding-bottom:5px;'>";
	}

	/**
	 * Get closing HTML for this section.
	 * @return string
	 */
	protected function getClosing()
	{
		return "</div>";
	}

	/**
	 * Get header for this section.
	 * @return string
	 */
	protected function getHeader()
	{
		return "<p class=\"header\"><strong>{$this->title}</strong></p>";
	}

	/**
	 * Format each item in the dataset into HTML
	 * @param  array $items
	 * @return string
	 */
	protected function getItems($items)
	{
		$items = array_map(function ($item) {
			return $this->formatItem($item);
		}, $items);
		
		return implode("", $items);
	}

	/**
	 * Format a given item into HTML
	 * @param  object $item
	 * @return string
	 */
	protected function formatItem($item) {}
}