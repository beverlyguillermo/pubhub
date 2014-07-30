<?php 

namespace app\controllers;
use app\models\Issues;
use \app\base\View;

class IssuesController extends \app\base\Controller
{
    protected $objectName = "Issues";

    public function __construct($action, $options)
    {
        parent::__construct($action, $options);
    }

	public function show()
    {
        // call issues model
        $this->model->findByCriteria();
        $this->model->setTemplate();

        // print_r($this->model->data); die();

        // populate view
        $this->render($this->model->data["template"]);
    }
}