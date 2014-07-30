<?php

namespace email\data;

class EventGetter extends SectionGetter
{
	protected $title = "Upcoming Events";

	protected function getData()
	{
		// get today's and tomorrow's events
		$today = $this->get("events", array(
			"per_page" => -1,
			"date" => date("Y-m-d", time())
		));

		$tomorrow = $this->get("events", array(
			"per_page" => -1,
			"date" => date("Y-m-d", strtotime("+1 day"))
		));

		// if it's not friday, return
		if (date("N", time()) != 5) {
			return $this->combineData(array($today, $tomorrow));
		}


		// if today is friday, also get sunday and monday's events
		$sunday = $this->get("events", array(
			"per_page" => -1,
			"date" => date("Y-m-d", strtotime("+2 days"))
		));

		$monday = $this->get("events", array(
			"per_page" => -1,
			"date" => date("Y-m-d", strtotime("+3 days"))
		));

		return $this->combineData(array($today, $tomorrow, $sunday, $monday));
	}
	
	protected function formatItem($item)
	{
		$html = "<p><strong><a href=\"{$item->url}\" style=\"font-weight: bold;\">{$item->name}</a></strong><br />";

		$html .= "<em>When: " .  $this->findDate($item) . $this->findTime($item);

		$locationObj = !empty($item->_embedded->locations) ? $item->_embedded->locations[0] : null;
		if ($locationObj) {
			$location = $locationObj->name;
			if (!empty($locationObj->parent)) {
				$location .= ", {$locationObj->parent->name}";
			}
			$html .= "<br />Where: {$location}</em>";
		}

		$html .= "</p>";

		$html .= !empty($item->excerpt) ? "<p>" . $item->excerpt . "</p>" : $item->description;

		// add a little extra space
		$html .= "<br />";

		return $html;
	}

	protected function combineData($data)
	{
		$events = [];

		foreach ($data as $daysEvents) {
			foreach ($daysEvents as $event) {

				// store by event ID to prevent duplicates
				$events[$event->id] = $event;
			}
		}

		return $events;
	}

	protected function findDate($item)
	{
		$niceDate = "";

		$startDate = strtotime($item->start_date);
		$endDate = strtotime($item->end_date);

		$todayDate = strtotime(date("Y-m-d", time()));


		// one day event
		if ($startDate == $endDate) {

			if ($startDate == $todayDate) {
				$niceDate = "Today";
			} else {
				// saturday or sunday event
				$niceDate = date("F j", $startDate);
			}

		// multiday event
		} else {
			$niceDate = date("F j", $startDate) . "-" . date("F j", $endDate);
		}

		$search = array("January", "February", "August", "September", "October", "November", "December");
		$replace = array("Jan", "Feb", "Aug", "Sept", "Oct", "Nov", "Dec");

		return str_replace($search, $replace, $niceDate);
	}


	protected function findTime($item)
	{
		if (!isset($item->start_time)) {
			return "";
		}

		$unixStart = strtotime($item->start_time);
		$unixEnd = isset($item->end_time) ? strtotime($item->end_time) : null;

		$startTime = date("g:ia", $unixStart);

		if (date("i", $unixStart) == "00") {
			$startTime = date("ga", $unixStart);
		}

		$niceTime = $startTime;

		if ($unixEnd && $unixEnd != $unixStart) {

			if (date("a", $unixStart) == date("a", $unixEnd)) {
				$niceTime = date("g", $unixStart);
			}

			$endTime = date("g:ia", $unixEnd);

			if (date("i", $unixEnd) == "00") {
				$endTime = date("ga", $unixEnd);
			}

			$niceTime = $niceTime . "-" . $endTime;

		}

		return ", " . $niceTime;
	}
}
