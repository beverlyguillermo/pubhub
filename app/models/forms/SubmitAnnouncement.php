<?php

namespace app\models\forms;

class SubmitAnnouncement extends \app\models\Forms
{
	public function __construct($formId, $router, $http = null)
	{
		$this->config = array(
			"id" => "submitAnnouncement",
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

		$this->form->addFieldset("Announcement Information")
       		->addTextbox("date", "Date to appear", array("required" => true, "defaultValue" => $this->defaultDate()))
        	->addTextbox("title", "Title", array("required" => true))
        	->addTextarea("summary", "Summary", array("required" => true, "afterElement" => "<div class='help'>This summary will appear on the listing of announcements (255 chars maximum).</div></div>"))
        	->addTextarea("fulltext", "Announcement", array("required" => true, "afterElement" => "<div class='help'>Please include the full text of the announcement. If web addresses are included please type out the full URL including http://</div></div>"));

       	$this->form->addFieldset("Contact Information")
       		->addText("contact note", "<p><em>Note: this information will be used by announcement administrators to contact you with questions; it will not be published.</em></p>")
       		->addTextbox("contact_name", "Name", array("required" => true))
       		->addEmail("contact_email", "Email", array("required" => true))
       		->addTextbox("contact_phone", "Phone");

		$this->form->addHoneypot();
		
		$this->form->addSubmit("submit", "Submit Announcement", array("attr" => array("class" => "btn"), "beforeElement" => "<div class='form_field submit force'>",));
	}

	protected function defaultDate()
	{
		// after 2pm, earlier schedule date is 2 days from today
		$addDays = (date("G", time()) >= 14) ? 2 : 1;

		$date = strtotime("+{$addDays} days");

		if (!$this->isWeekend($date)) {
			return date("m/d/Y", $date);
		}

		while ($this->isWeekend($date)) {
			$addDays++;
			$date = strtotime("+{$addDays} days");
		}

		return date("m/d/Y", $date); 
	}

	protected function isWeekend($date) {
		return (date("N", $date) >= 6);
	}

	protected function validateForm()
	{
		// validate the date
		
		if ($error = $this->checkDate($this->requestParams["date"])) {
			$this->form->setErrorMessage($error);
		}

		if ($error = $this->checkSummary($this->requestParams["summary"])) {
			$this->form->setErrorMessage($error);
		}

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

	protected function checkDate($date)
	{
		$givenDate = strtotime($date);
		$soonest = $this->defaultDate();

		if ($givenDate < strtotime($soonest)) {
			return "The \"Date to appear\" field is not valid. The soonest an announcement can appear is {$soonest}. Please make the needed correction.";
		}

		return false;
	}

	protected function checkSummary($text)
	{
		$length = strlen($text);

		if (strlen($text) > 255) {
			return "The \"Summary\" field contains {$length} characters, but it is limited to 255 characters. Please shorten the summary.";
		}

		return false;
	}

	protected function resolve($result)
	{
		if ($result) {
			$id = $result->results->id;
			$this->sendEmail($id);
			$_SESSION["submitAnnouncement"] = array(); // clear form
			return $this->router->redirect("/submit-announcement/thanks");
		} else {
			return $this->form->setErrorMessage("Something has gone wrong. Please try to submit the form again. If the problem persists, please submit the announcement directly to <a href=\"mailto:dalexander@jhu.edu\">Dave Alexander</a>.");
		}
	}

	protected function postToApi()
	{
		$result = $this->http->clearPrevious()
					   ->setEndpoint("announcements")
					   ->addQueryStringParams($this->formatParams())
					   ->post();

		return property_exists($result, "error") ? 0 : $result;
	}

	protected function formatParams()
	{
		$params = $this->requestParams;

		$params["date"] = date("Y-m-d", strtotime($params["date"]));
		$params["author"] = "Hub staff report";

		return $params;
	}

	protected function sendEmail($id)
	{
		// send an email
		$body = $this->buildBody($id);

		$this->mailer->newMessage()
			->setMessageSubject("Announcement submitted to the Hub")
			->setMessageFrom(array("noreply@jhu.edu" => "The Hub"))
			->setMessageTo(array("dalexander@jhu.edu", "anns@jhu.edu", "acl@jhu.edu"))
			// ->setMessageTo(array("jwachter@jhu.edu"))
			->setMessageBody($body, "text/html");

		return $this->mailer->sendMessage();
	}

	protected function buildBody($id)
	{
		$body = "<h1>Announcement submitted to the Hub</h1><h2>Submitted by</h2>";
		$body .= "<p><strong>Name</strong>: {$this->requestParams['contact_name']}<br />";
		$body .= "<strong>Email</strong>: <a href='mailto:{$this->requestParams['contact_email']}'>{$this->requestParams['contact_email']}</a><br />";
		$body .= "<strong>Phone</strong>: <a href='mailto:{$this->requestParams['contact_phone']}'>{$this->requestParams['contact_phone']}</a></p>";

		$body .= "<h2>Announcement details</h2>";

		$body .= "<p><strong>Date to appear</strong>: {$this->requestParams['date']}<br />";
		$body .= "<p><strong>Title</strong>: {$this->requestParams['title']}<br />";
		$body .= "<p><strong>Summary</strong>: {$this->requestParams['summary']}<br />";
		$body .= "<p><strong>Full text</strong>: {$this->requestParams['fulltext']}<br />";
		
		$apiurl = API_URL;
		$body .= "<h2><a href='{$apiurl}factory/node/{$id}/edit'>Edit now</a></h2>";

		return $body;
	}
}
