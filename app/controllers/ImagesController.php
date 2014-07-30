<?php 

namespace app\controllers;
use app\models\Images;
use \app\base\View;

class ImagesController extends \app\base\Controller
{
    protected $objectName = "Images";

    public function __construct($action, $options)
    {
        parent::__construct($action, $options);
    }

	public function show()
    {
        // call articles model
        $this->model->findById();
        $this->model->findGalleryTitle();
        $this->model->setTemplate();

        // populate view
        $this->render($this->model->data["template"]);
    }
}