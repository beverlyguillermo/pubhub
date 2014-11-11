<?php
namespace app\models\pages;
class Events extends \app\models\Pages
{
    protected $today;
    protected $thisWeek;
    /**
     * Date filters
     * @var array
     */
    protected $dateFilters = array(
        "today",
        "this week"
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
            
            $this->data["results"]["endpoint"] = $this->getEvents("+1 weeks");
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
                echo 
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
     * Set some dates to use in isXXXX() methods
     */
    protected function setDates()
    {
        $this->today = array(
            "start" => strtotime("today 00:00"),
            "end" => strtotime("today 23:59")
        );
        // $this->thisWeek = array(
        //     "start" => strtotime("today 00:00"),
        //     "end" => strtotime("today 23:59 +6 days")
        // );
    }
    /**
     * Determine if the event takes place today
     * @param  object $event   Event object
     * @return boolean
     */
    protected function isToday($event)
    {
        $start = strtotime($event->start_date . " 00:00");
        $end = strtotime($event->end_date . " 23:59");
        // echo "\nToday start: {$this->today['start']}, Event start: {$start}\n";
        // echo "Today end: {$this->today['end']}, Event end: {$end}\n";
        
        if ($this->today["start"] >= $start && $this->today["end"] <= $end) return true;
        return false;
    }
    /**
     * Determine if the event takes place this week
     * @param  object $event   Event object
     * @return boolean
     */
    protected function isThisWeek($event)
    {
        // all events in this API pull happen this week.
        return true;
    }
}
