<?php

namespace app\models\pages;

class Events extends \app\models\Pages
{
	protected $today;
	protected $thisWeek;
    protected $nextTwoWeeks;

	/**
	 * Date filters
	 * @var array
	 */
	protected $dateFilters = array(
		"today",
		"this week",
        "next two weeks"
	);

	public function __construct($options = array())
	{
    	$this->setDates();
		parent::__construct($options);
	}

	public function getPageData()
	{
        parent::getPageData();

        // only if this is the /events page (exclude subpages)
        if ($this->page_info["slug"] == "events") {
            
            $this->data["results"]["endpoint"] = $this->getEvents("+2 weeks");

            $this->data["results"]["locations"] = $this->getFilters("locations", false);
            $this->data["results"]["topics"] = $this->getFilters("topics");

            unset($this->data["results"]["topics"][35]); // university news
            unset($this->data["results"]["topics"][37]); // voices and opinion

            $this->matchFiltersToContent("locations", $this->data["results"]["locations"], $this->data["results"]["endpoint"], true);
            $this->matchFiltersToContent("topics", $this->data["results"]["topics"], $this->data["results"]["endpoint"], true);

            $this->matchDateFiltersToContent($this->data["results"]["endpoint"]);

        }
    }

    protected function getEvents($timeOut = "+1 week", $page = 1)
    {
        $events = [];

        $startEndDates = array(
            date("Y-m-d", time()),
            date("Y-m-d", strtotime($timeOut))
        );

        $response = $this->http->setEndpoint("events")
            ->addQueryStringParam("date", implode(",", $startEndDates))
            ->addQueryStringParam("page", $page)
            ->addQueryStringParam("per_page", "-1")
            ->get();

        if (isset($response->_embedded->events) && !empty($response->_embedded->events)) {

            foreach ($response->_embedded->events as $event) {
                $events[] = $event;
            }

            if (isset($response->_links->next)) {
                $more = $this->getEvents($timeOut, (int) $response->page + 1);

                foreach ($more as $event) {
                    $events[] = $event;
                }
            }

        } else {
            $this->router->notFound();
        }

        return $events;
    }

	/**
	 * Set some dates to use in isXXXX() methods
	 */
	protected function setDates()
	{
		$this->today = date("Y-m-d", strtotime("today"));
        $this->thisWeek = date("W", strtotime("this week"));
        $this->nextTwoWeeks = date("U", strtotime("+2 weeks 11:59pm"));
	}

	/**
	 * Get an array of date filters with nice name,
	 * slug, count of events that match the date, and
	 * the function used to test whether an event matches
	 * a particular date filter.
	 * @return array Date filters
	 */
    protected function getDateFilters()
    {
    	return array_map(function($v) {

    		return array(
    			"name" => ucwords($v),
    			"slug" => str_replace(" ", "-", $v),
    			"count" => 0,
    			"test" => "is" . str_replace(" ", "", ucwords($v))
    		);

    	}, $this->dateFilters);
    }

    /**
     * Matched filters against events. Create a count in the filters array
     * that keeps track of how many events are within a particular filter.
     * Add the slug of each filter to each item's filterClasses property.
     * @param  array $content
     * @return null
     */
    protected function matchDateFiltersToContent(&$content)
    {
    	$filters = $this->getDateFilters();

    	foreach ($content as $item) {

    		if (!property_exists($item, "filterClasses")) {
    			$item->filterClasses = array();
    		}

    		foreach ($filters as &$filter) {
    			$testFunction = $filter["test"];
    			if ($this->$testFunction($item)) {
    				$item->filterClasses[] = $filter["slug"];
    				$filter["count"]++;
    			}
    		}
    	}

    	$this->data["results"]["dates"] = $filters;
    }

    /**
     * Get the given event's start date in a given format.
     * @param  object $event   Event object
     * @param  string $format  PHP date() format
     * @return string Formatted date
     */
    protected function getStartDate($event, $format = "Y-m-d")
    {
    	return date($format, strtotime($event->start_date));
    }

    /**
     * Determine if the event takes place today
     * @param  object $event   Event object
     * @return boolean
     */
    protected function isToday($event)
    {
    	return $this->getStartDate($event) == $this->today;
	}

	/**
     * Determine if the event takes place this week
     * @param  object $event   Event object
     * @return boolean
     */
	protected function isThisWeek($event)
	{
		return $this->getStartDate($event, "W") == $this->thisWeek;
	}

    protected function isNextTwoWeeks($event)
    {
        return $this->getStartDate($event, "U") <= $this->nextTwoWeeks;
    }
}