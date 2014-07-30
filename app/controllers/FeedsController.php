<?php 

namespace app\controllers;
use app\models\Feeds;
use \app\base\View;

class FeedsController extends \app\base\Controller
{
	protected $objectName = "Feeds";

    public function __construct($action, $options)
    {
        parent::__construct($action, $options);
    }

    /**
     * Analyzes the $options["type"] variable to determine if this
     * type of feed has its own model apart from the base Feeds model.
     * @return string Path to model to instantiate
     */
    protected function getModelName()
    {
        if (isset($this->options["type"])) {
            $type = ucfirst($this->options["type"]);
            $class = "\\app\\models\\feeds\\" . $type;
            if (class_exists($class)) {
                return $class;
            }
        }
        
        return "\\app\\models\\" . $this->objectName;
    }

	public function show()
    {
        $this->model->getFeed();
    }
}