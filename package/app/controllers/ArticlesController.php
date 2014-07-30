<?php 

namespace app\controllers;
use app\models\Articles;
use \app\base\View;

class ArticlesController extends \app\base\Controller
{
    protected $objectName = "Articles";

    public function __construct($action, $options)
    {
        parent::__construct($action, $options);
    }

	public function show()
    {
        // call articles model
        $this->model->findByCriteria();
        $this->model->setTemplate();

        // print_r($this->model->data); die();

        // populate view
        $this->render($this->model->data["template"]);
    }
}