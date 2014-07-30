<?php

namespace app\controllers;

class AlertsController extends \app\base\Controller
{
	protected $objectName = "Alerts";

	public function index()
	{
		$alert = $this->model->getLastValidated();
		$admin = $this->model->getAdminData();

		// var_dump($alert); die();

		if ($alert["status"] == 1 || $admin["auto"]) {
			$this->edit($admin, $alert);
		} else {
			$this->model->data["page_title"] = "Create a New Alert";
			$this->render("alerts/index", array("admin" => $admin));
		}
	}

	protected function edit($admin, $alert)
	{
		$data = array("admin" => $admin, "alert" => $alert);

		$this->model->data["page_title"] = "Edit Alert";
		$this->render("alerts/edit", $data);
	}

	public function create()
	{
		$req = $this->router->request();
		$params = $this->router->request()->params();

		$params["start_time"] = strtotime($params["start_time"]);
		$params["end_time"] = strtotime($params["end_time"]);

		// var_dump(array_filter($params)); die();

		$this->model->create(array_filter($params + array("status" => 1)));
		$this->router->redirect("/manager/alerts");
	}

	public function update()
	{
		$req = $this->router->request();
		$params = $this->router->request()->params();

		// print_r($params); echo "<br><br><hr><br><br>";

		$params["start_time"] = strtotime($params["start_time"]);
		$params["end_time"] = strtotime($params["end_time"]);

		// print_r($params); die();

		$this->model->update($this->id, array_filter($params));
		$this->router->redirect("/manager/alerts");
	}

	public function delete()
	{
		$this->model->update($this->id, array("status" => 0));
		$this->router->redirect("/manager/alerts");
	}
}