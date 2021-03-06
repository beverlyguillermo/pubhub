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

	public function setTemplate()
	{
		$this->data["layout"] = "hub";
		$this->data["template"] = "events/event";
	}
}