<?php

namespace app\models\forms;

class SubmitArticle extends \app\models\Forms
{
	public function __construct($formId, $router, $http = null)
	{
		$this->config = array(
			"id" => "submitArticle",
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

		$this->form->addTextbox("your_name", "Your name", array("required" => true));
		$this->form->addEmail("email", "Email", array("required" => true));
		$this->form->addTextbox("affiliation", "Johns Hopkins affiliation");
		$this->form->addTextbox("headline", "Headline", array("required" => true));
		$this->form->addTextarea("text", "In a few sentences, tell us what the story is about and why it's important or interesting (or, if you're feeling ambitious, submit a completed article or article URL)", array("required" => true, "beforeElement" => "<div class='form_field nofloat force'>", "attr" => array("class" => "wysiwyg", "data-editor-name" => "mini")));
		$this->form->addHoneypot();
		
		$this->form->addSubmit("submit", "Submit", array("attr" => array("class" => "btn"), "beforeElement" => "<div class='form_field submit force'>",));
	}

	protected function validateForm()
	{
		if ($this->form->isValid()) {
			if ($this->form->passedHoneypot()) {
	            $this->saveInDatabase();
				$result = $this->sendEmail();
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
			$_SESSION["submitArticle"] = array();
			return $this->router->redirect("/submit-article/thanks");
		} else {
			return $this->form->setErrorMessage("Something has gone wrong. Please try to submit the form again. If the problem persists, please submit the article directly to <a href=\"mailto:dalexander@jhu.edu\">Dave Alexander</a>.");
		}
	}

	protected function saveInDatabase()
	{
		$this->tableName = "form_submit_article";
		return $this->create(array(
			"name" => $this->requestParams["your_name"],
			"email" => $this->requestParams["email"],
			"affiliation" => $this->requestParams["affiliation"],
			"headline" => $this->requestParams["headline"],
			"text" => $this->requestParams["text"],
		));
	}

	protected function sendEmail()
	{
		// send an email
		$body = $this->buildBody();

		$this->mailer->newMessage()
			->setMessageSubject("Article suggested to the Hub")
			->setMessageFrom(array("noreply@jhu.edu" => "The Hub"))
			->setMessageTo(array("dalexander@jhu.edu"))
			// ->setMessageTo(array("jwachter@jhu.edu"))
			->setMessageBody($body, "text/html");

		return $this->mailer->sendMessage();
	}

	protected function buildBody()
	{
		$body = "<h1>Article suggested to the Hub</h1><h2>Submitted by</h2><p><strong>Name</strong>: {$this->requestParams['your_name']}<br />";

		if (!empty($this->requestParams["affiliation"])) {
			$body .= "<strong>Affiliation</strong>: {$this->requestParams['affiliation']}<br />";
		}
		
		$body .= "<strong>Email</strong>: <a href='mailto:{$this->requestParams['email']}'>{$this->requestParams['email']}</a></p>";
		$body .= "<h2>Article details</h2><p><strong>Headline</strong>: {$this->requestParams['headline']}";

		$body .= "</p>";

		if (!empty($this->requestParams["text"])) {
			$body .= "<p><strong>Text</strong><br /> {$this->requestParams['text']}</p>";
		}

		return $body;
	}
}