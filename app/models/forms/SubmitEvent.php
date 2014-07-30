<?php

namespace app\models\forms;

class SubmitEvent extends \app\models\Forms
{
	public function __construct($formId, $router, $http = null)
	{
		$this->config = array(
			"id" => "submitEvent",
			"action" => $router->request()->getResourceUri(),
			"beforeElement" => "<div class='form_field force'>",
            "afterElement" => "</div>",
            "attr" => array(
            	"class" => "horizontal"
            )
        );

        parent::__construct($formId, $router, $http);
	}

	protected function make()
	{
		parent::make();

		$campuses = array(
			"None",
			"Applied Physics Laboratory",
			"Columbia Center",
			"East Baltimore Campus",
			"Harbor East",
			"Homewood Campus",
			"Montgomery County Campus",
			"Mount Washington",
			"Peabody Institute",
			"SAIS Washington",
			"Washington, D.C. Center"
		);

        $this->form->addFieldset("About the Event")
        	->addTextbox("name", "Name of the event", array("required" => true))
        	->addTextarea("description", "Tell us about the event", array("required" => true))
       		->addTextbox("date", "Date (e.g. 8/28/13)", array("required" => true))
       		->addTextbox("start_time", "Start time (e.g. 2pm)")
       		->addTextbox("end_time", "End time")
       		->addSelect("campus", "Campus", $campuses, array("required" => true))
       		->addTextbox("building", "Building and/or room", array("required" => true))
       		->addTextarea("additional_info", "Additional information");

       	$this->form->addFieldset("Contact Information")
       		->addText("contact note", "<p><em>Note: this information will be used by calendar administrators to contact you with questions; it will not be published.</em></p>")
       		->addTextbox("contact_name", "Name", array("required" => true))
       		->addEmail("contact_email", "Email", array("required" => true))
       		->addTextbox("contact_phone", "Phone");

		$this->form->addHoneypot();
		
		$this->form->addSubmit("submit", "Submit Event", array("attr" => array("class" => "btn"), "beforeElement" => "<div class='form_field submit force'>",));
	}

	protected function validateForm()
	{
		if ($this->form->isValid()) {
			if ($this->form->passedHoneypot()) {
				$result = $this->postToApi();
	        } else {
	            // we have a bot -- fake a good result
	            $result = true;
	        }
			
			$this->resolve($result);
		}
	}

	protected function resolve($result)
	{
		if ($result) {
			$id = $result->results->id;
			$this->sendEmail($id);
			$_SESSION["submitEvent"] = array(); // clear form
			return $this->router->redirect("/submit-event/thanks");
		} else {
			return $this->form->setErrorMessage("Something has gone wrong. Please try to submit the form again. If the problem persists, please submit the event directly to <a href=\"mailto:dalexander@jhu.edu\">Dave Alexander</a>.");
		}
	}

	protected function postToApi()
	{
		$result = $this->http->clearPrevious()
					   ->setEndpoint("events")
					   ->addQueryStringParams($this->formatParams())
					   ->post();

		return property_exists($result, "error") ? 0 : $result;
	}

	protected function formatParams()
	{
		$params = $this->requestParams;

		$params["date"] = date("Y-m-d", strtotime($params["date"]));
		$params["start_time"] = date("H:i", strtotime($params["start_time"]));
		$params["end_time"] = date("H:i", strtotime($params["end_time"]));

		return $params;
	}

	protected function sendEmail($id)
	{
		// send an email
		$body = $this->buildBody($id);

		$this->mailer->newMessage()
			->setMessageSubject("Event suggested to the Hub")
			->setMessageFrom(array("noreply@jhu.edu" => "The Hub"))
			->setMessageTo(array("dalexander@jhu.edu", "rturning@jhu.edu", "Jkirsch9@jhu.edu", "anns@jhu.edu", "acl@jhu.edu"))
			// ->setMessageTo(array("jwachter@jhu.edu"))
			->setMessageBody($body, "text/html");

		return $this->mailer->sendMessage();
	}

	protected function buildBody($id)
	{
		$body = "<h1>Event suggested to the Hub</h1><h2>Submitted by</h2>";
		$body .= "<p><strong>Name</strong>: {$this->requestParams['contact_name']}<br />";
		$body .= "<strong>Email</strong>: <a href='mailto:{$this->requestParams['contact_email']}'>{$this->requestParams['contact_email']}</a></p>";

		$body .= "<h2>Event details</h2>";

		$body .= "<p><strong>Name</strong>: {$this->requestParams['name']}<br />";
		$body .= "<p><strong>Description</strong>: {$this->requestParams['description']}<br />";
		$body .= "<p><strong>Date</strong>: {$this->requestParams['date']}<br />";
		
		$apiurl = API_URL;
		$body .= "<h2><a href='{$apiurl}factory/node/{$id}/edit'>Edit now</a></h2>";

		return $body;
	}
}