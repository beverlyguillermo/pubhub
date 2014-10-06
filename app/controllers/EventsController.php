<?php

namespace app\controllers;
use app\models\Events;
use \app\base\View;
use \app\workers\ICalParse;

class EventsController extends \app\base\Controller
{
    protected $objectName = "Events";

    public function __construct($action, $options)
    {
        parent::__construct($action, $options);
    }

	public function show()
	{
        // call model
        $this->model->findByCriteria();
        $this->model->setTemplate();

        // print_r($this->model->data); die();

        // populate view
        $this->render($this->model->data["template"]);
	}

    public function subscribe()
    {
      $request = $this->router->request();
      $params = $request->params();
      $today  = strtotime('Today');
      $last_sunday = strtotime('last sunday', $today);
      $one_week    = strtotime('+1 week -1 day', $last_sunday);
      $two_weeks   = strtotime('+2 weeks -1 day', $last_sunday);
      if (!isset($params['date']) || empty($params['date']))
        $params['date'] =  array('.next-two-weeks');
      switch($params['date'][0]) {
        case '.today':
          $params['date'] = array(date('Y-m-d', $today), date('Y-m-d', $today));
          $params['selected'] = 'Today';
        break;
        case '.this-week':
          $params['date'] = array(date('Y-m-d', $last_sunday), date('Y-m-d', $one_week));
          $params['selected'] = 'This Week';
        break;
        case '.next-two-weeks':
          $params['date'] = array(date('Y-m-d', $last_sunday), date('Y-m-d', $two_weeks));
          $params['selected'] = 'Next Two Weeks';
        break;
        default:
          $params['selected'] = 'Custom Date';
          // Use the custom date parameters
      }
      $this->model->findByCriteria2(array(
        'date' => $params['date'],
      ));
      $calendar = new ICalParse($params);
      try {
        $calendar->parse($this->model->data['results']);
      } catch (Exception $e) {
        $this->log->addError("Triggering 'notFound' from " . __FILE__ . ":" . __LINE__, array($e->getMessage()));
      }
      header('Content-type: text/calendar; charset=utf-8');
      header('Content-Disposition: attachment; filename="jhu_hub_events.ics"');
      header('Content-Disposition: inline; filename=jhu_hub_events.ics');
      header('Last-Modified: '.gmdate('D, d M Y H:i:s', strtotime('now')));
      $calendar->createCalendar();
      exit();
    }
}
