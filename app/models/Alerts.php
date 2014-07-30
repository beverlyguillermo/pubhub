<?php

namespace app\models;

class Alerts extends Model
{
	protected $tableName = "alerts";

	protected function getLast()
	{
		$sql = "SELECT * FROM {$this->tableName} ORDER BY id DESC LIMIT 1";

		$this->pdoPrepared = $this->pdo->prepare($sql);
		$this->pdoPrepared->execute();
		$resultset = $this->pdoPrepared->fetchAll(\PDO::FETCH_ASSOC);
		return array_pop($resultset);
	}

	public function getLastValidated()
	{
		$alert = $this->getLast();
		if (empty($alert)) return null;
		if (is_null($alert["start_time"])) { $alert["start_time"] = time() - 50000; }

		if ($alert["status"] == 1) {
			$now = time();
			if (is_null($alert["end_time"])) {
				$status = $now > $alert["start_time"];
			} else {
				$status = $now > $alert["start_time"] && $now < $alert["end_time"];
			}
			$alert["status"] = (int) $status;
			$alert["start_time"] = !empty($alert["start_time"]) ? date("F jS, Y g:ia", $alert["start_time"]) : "";
			$alert["end_time"] = !empty($alert["end_time"]) ? date("F jS, Y g:ia", $alert["end_time"]) : "";
		} else {
			$alert = array("start_time" => NULL, "end_time" => NULL) + $alert;
		}

		return $alert;
	}

	public function getAdminData()
	{
		$data = array();
		$alert = $this->getLastValidated();
		$now = time();
		$format = "F jS g:ia";

		if ($now < strtotime($alert["start_time"])) {
			$data["message"] = "There is no alert currently set for the Hub, but the following message is scheduled to turn on (" . $alert["start_time"] . ")";
			$data["auto"] = true;
			$data["link_text"] = "Turn this alert off now.";
		} else if (!is_null($alert["end_time"]) && $now < strtotime($alert["end_time"])) {
			$data["message"] = "The following alert is currently ON for the Hub. It's scheduled to automatically turn off (" . $alert["end_time"] . ")";
			$data["auto"] = true;
			$data["link_text"] = "Cancel this alert and delete it.";
		} else if ($alert["status"] == 0) {
			$data["message"] = "There is no alert currently set for the Hub. To schedule a new alert message, fill out the form below.";
			$data["auto"] = false;
		} else {
			$data["message"] = "The following alert is currently ON for the Hub, and it will NEVER turn off until you turn it off manually.";
			$data["auto"] = false;
			$data["link_text"] = "Turn this alert off now.";
		}

		return $data;
	}
}