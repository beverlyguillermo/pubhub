<?php

namespace app\models\feeds;

class Event extends \app\models\Feeds
{
	public function __construct($options)
    {
    	parent::__construct($options);
    }

	protected function getData($count = 20)
	{
		$this->http->setEndpoint("events")
			->addQueryStringParam("per_page", $count);

		if ($this->today) {
			$this->http->addQueryStringParam("date", date("Y-m-d", time()));
		}

		$response = $this->http->get();
		return $response->_embedded->events;
	}

	protected function filterData($events)
	{
		return $this->addDate($events);
	}

	protected function addDate($events)
	{
		if (empty($events)) {
			return $events;
		}

		return array_map(function ($event) {
			unset($event->publish_date);
			$desc = !is_null($event->description) ? $event->description : "";
			$event->description = "<p>" . $this->getEventNiceDateTime($event) . "<br />" . $this->getNiceLocation($event) . "</p>" . $desc;
			return $event;
		}, $events);
	}

	protected function getDataMap()
	{
		$map = parent::getDataMap();
		$map["name"] = "title";

		return $map;
	}

	protected function createChannel()
	{
		$channel = parent::createChannel();

		$channel["title"] = "Hub - Events";
		$channel["link"] = $this->getBaseUrl() . "/events";
		$channel["description"] = "Events from the Johns Hopkins news network";

		return $channel;
	}

	protected function getEventNiceDateTime($event)
  {
    $niceDate = $this->getNiceDate($event);

    $html = "When: {$niceDate}";

    return $html;
  }

  protected function getNiceDate($event)
  {
    $start = strtotime($event->start_date . " " . $event->start_time);
    $end = strtotime($event->end_date . " " . $event->end_time);

    $niceDate = date("F jS", $start);

    if (!is_null($event->start_time)) {
      $niceDate .= " " . date("g:ia", $start);
    }

    if ((!is_null($event->end_date) && $event->end_date !== $event->start_date) || !is_null($event->end_time)) {
      $niceDate .= " - ";
    }

    if (!is_null($event->end_date) && $event->end_date !== $event->start_date) {
      $niceDate .= date("F jS", $end) . " ";
    }

    if (!is_null($event->end_time)) {
      $niceDate .= date("g:ia", $end);
    }

    return $niceDate;

  }

	protected function getNiceLocation($event)
	{
		if (empty($event->_embedded->locations)) {
			return "";
		}

		$location = $event->_embedded->locations[0];

		$name = $location->name;

		if (!empty($location->parent)) {
			$name .= " {$location->parent->name}";
		}

		return "Where: {$name}";
	}
}
