<?php

namespace app\base;
use \app\base\View;
use \app\workers\Router;
use \app\workers\Logger;

class Controller
{
	protected $options;

	protected $objectName;
	protected $model;
	protected $id;
	protected $router;

	protected $log;

	public function __construct($action, $options)
	{
		$this->options = $options;
		$this->router = Router::getInstance();
		$this->id = isset($this->options["id"]) ? $this->options["id"] : null;

		$this->log = Logger::getInstance();

		$modelName = $this->getModelName();

		$this->model = new $modelName($this->options);
		View::setModel($this->model);
		View::setLogger($this->log);

		$action = $action ? $action : "index";

		$this->$action();
	}

	protected function getModelName()
	{
		return "\\app\\models\\" . $this->objectName;
	}

	public function render($templateName, $addData = array())
	{
		$alertFinder = new \app\models\Alerts();
		$alert = $alertFinder->getLastValidated();

		$data = array("alert" => $alert) + $addData;
		
		echo View::render($templateName, $data);
	}

	public function index()
	{
		// Gather data
		$this->model->create(array(
			"title" => "Topics: Arts & Culture's Effect",
			"stream" => "topics/arts-culture-effect",
			"template" => "topics"
		));

		// Render template?
		$this->render(strtolower($this->objectName) . "/index");
	}

}