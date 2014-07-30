<?php 

namespace app\controllers;
use app\models\Announcements;
use \app\base\View;

class AnnouncementsController extends \app\base\Controller
{
    protected $objectName = "Announcements";

    public function __construct($action, $options)
    {
        parent::__construct($action, $options);
    }

	public function show()
    {
        // call announcements model
        $this->model->findByCriteria();
        $this->model->setTemplate();

        // print_r($this->model->data); die();

        // populate view
        $this->render($this->model->data["template"]);
    }
}