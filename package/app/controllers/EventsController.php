<?php 

namespace app\controllers;
use app\models\Events;
use \app\base\View;

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
}