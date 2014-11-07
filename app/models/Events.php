<?php

namespace app\models;

class Events extends Model
{
	protected $tableName = "events";

	public function __construct($options)
	{
		parent::__construct($options);
		$this->data["section"] = "event";
		$this->data["preview"] = $this->preview;
	}

	public function findByCriteria()
	{
        $startDate = $this->year . "-" . $this->month . "-" . $this->day;

		$this->http->setEndpoint("events")
			->addQueryStringParam("start_date", $startDate)
			->addQueryStringParam("slug", $this->slug)

			// allow for expired events to have their own page
			->addQueryStringParam("expired", true);

		// Enable preview, if necessary
		if ($this->preview) {
			$this->http->addQueryStringParam("preview", true);
		}

		$response = $this->http->get();

		if (!empty($response->_embedded->events)) {
			$results = array_shift($response->_embedded->events);
			$this->data["results"] = $results;

		} else {
			$this->router->notFound();
			$this->log->addError("Triggering 'notFound' from " . __FILE__ . ":" . __LINE__, array(json_encode($response)));
		}
	}

	public function findByCriteria2($params = array())
	{

		$this->http->setEndpoint("events")

		// allow for expired events to have their own page
		->addQueryStringParam("expired", true);


		if (isset($this->year) && isset($this->month) && isset($this->day)) {
			$startDate = $this->year . "-" . $this->month . "-" . $this->day;
			$this->http->addQueryStringParam("start_date", $startDate);
		} else if (isset($params['date'])) {
			$startDate = vsprintf("%s,%s", $params['date']);
			$this->http->addQueryStringParam("date", $startDate);
		}

		if (isset($this->slug)) {
			$this->http->addQueryStringParam("slug", $this->slug);
		}

		// Get all data within the available date range
		$this->http->addQueryStringParam("page", 1);
		$this->http->addQueryStringParam("per_page", -1);

		// Enable preview, if necessary
		if ($this->preview) {
			$this->http->addQueryStringParam("preview", true);
		}

		$response = $this->http->get();

		if (!empty($response)) {
			$this->data["results"] = $response;
		} else {
			$this->data["results"] = null;
		}
	}

	public function setTemplate()
	{
		$this->data["layout"] = "hub";
		$this->data["template"] = "events/event";
	}
}
