<?php
namespace app\workers;

class ICalParse
{
  private $mapping;
  private $params;
  private $calendar;

  public function __construct($params = array())
  {

    // array('date', 'topic', 'campus');
    $this->params = $params;
    $this->calendar = new \Eluceo\iCal\Component\Calendar('hub.jhu.edu/events');

    $name = [];
    if (!empty($this->params['selected']))
      $name[] = $this->params['selected'];
    if (!empty($this->params['topic']))
      foreach ($this->params['topic'] as $topic)
        $name[] = ucwords(trim(preg_replace('/[\.\s-]/', ' ', $topic)));
    if (!empty($this->params['campus']))
      foreach ($this->params['campus'] as $campus)
        $name[] = ucwords(trim(preg_replace('/[\.\s-]/', ' ', $campus)));
    $this->calendar->setName(sprintf('HUB JHU Events [%s]', implode($name, ' ')));
  }

  public function parse($mapping = null )
  {
    if (is_null($mapping))
      return;
    if (isset($mapping->_embedded) && isset($mapping->_embedded->events))
    {
      $events = $mapping->_embedded->events;
      foreach ($events as $event)
      {
        $found = false;
        if (!empty($this->params['topic'])
            && isset($event->_embedded)
            && isset($event->_embedded->topics))
        {
          $topics = $event->_embedded->topics;
          foreach($topics as $topic) {
            // IF not already matching a topic or location, check it
            if (!$found) $found = in_array(sprintf(".%s", $topic->slug), $this->params['topic']);
          }
        }
        if (!empty($this->params['campus'])
            && isset($event->_embedded)
            && isset($event->_embedded->locations))
        {
          $locations = $event->_embedded->locations;
          foreach($locations as $location) {
            // IF not already matching a topic or location, check it
            if (!$found) $found = in_array(sprintf(".%s", $location->slug), $this->params['campus']);
          }
        }
        if ($found) continue;

        // check if multiple days
        $start_date = strtotime($event->start_date);
        $end_date = strtotime($event->end_date);
        $date_diff = abs(floor( ($start_date - $end_date) / (60*60*24)));
        for ($i = 0; $i <= $date_diff; $i++)
        {
          $the_date = date('Y-m-d', strtotime($event->start_date . " + " . $i . "days"));
          $start_time = $event->start_time;
          $end_time   = $event->end_time;
          $start_with_time = strtotime($the_date . " " . $start_time);
          if (!empty($end_time))
          {
            $end_with_time   = strtotime($the_date . " " . $end_time);
          }
          else {
            // No end time, setting to midnight otherwise calendar won't enable event.
            $end_with_time   = strtotime(date('Y-m-d 00:00:00', strtotime('+1 day', strtotime($the_date))));
          }

          $e = new \Eluceo\iCal\Component\Event(sprintf("%s-%s@hub.jhu.edu/events", $event->id, $start_with_time));
          $e->setDtStamp( new \DateTime("@$event->publish_date") );
          $e->setDtStart( new \DateTime("@$start_with_time") );
          $e->setDtEnd( new \DateTime("@$end_with_time") );
          $e->setSummary( $event->name );

          $description = "";
          if (isset($event->registration_required) && $event->registration_required == 1)
          {
            $description .= "REGISTRATION REQUIRED\r\n";
          }
          if (isset($event->featured) && $event->featured == 1)
          {
            $description .= "FEATURED EVENT\r\n";
          }
          if (isset($event->description))
          {
            $description .= strip_tags($event->description);
          }
          $e->setDescription( $description );

          if (isset($event->url) && $event->url != "")
          {
            $e->setUrl( $event->url );
          }

          $the_locations = array();
          $the_geo = array();
          if (isset($event->supplemental_location_info))
          {
            $the_locations[] = $event->supplemental_location_info;
          }
          if (isset($event->_embedded)
            && isset($event->_embedded->locations))
          {
            $locations = $event->_embedded->locations;
            foreach ($locations as $location)
            {
              $the_locations[] = $location->name;
              $the_locations[] = $location->address;
              $the_locations[] = $location->city;
              $the_locations[] = $location->state;
              $the_locations[] = $location->zipcode;
              //$the_locations[] = $location->parent->name;
              $the_geo[] = $location->latitude;
              $the_geo[] = $location->longitude;
            }
          }
          $e->setLocation(implode(" \n", array_filter($the_locations)), null, implode(',', array_filter($the_geo)));
          $this->calendar->addEvent($e);
        } // Multiple Days
      } // Iterating over events
    } // Found Events
  }

  public function createCalendar()
  {
      echo $this->calendar->render();
  }
}

?>
