<?php

namespace app\models\forms;
use \app\workers\HTTP;
use \Resty;

class Widget extends \app\models\Forms
{
	public function __construct($formId, $router, $http = null)
	{
		$this->config = array(
			"id" => "hubWidgetForm",
			"action" => $router->request()->getResourceUri(),
			"beforeElement" => "<div class='form_field force'>",
            "afterElement" => "</div>"
        );

        parent::__construct($formId, $router, $http);
	}

	protected function make()
	{
		parent::make();

		$this->form->addTextbox("title", "Widget title:", array("defaultValue" => "News from the Hub"));
		$this->form->addNumber("count", "Number of articles to display:", array("defaultValue" => 5));
		$this->form->addCheckbox("topics", "Retrieve articles from these topics:", $this->getTopicsOptions(), array("beforeElement" => "<div class='checkbox_group form_field force'>", "afterElement" => "</div>"));
		$this->form->addTextbox("tags", "Retrieve articles from these tags: <a href='#tagsQuestion' data-toggle='modal'><i class='icon-question-sign'></i></a>");
		$this->form->addRadio("theme", "Theme", array("light", "dark", "none"), array("defaultValue" => "light", "beforeElement" => "<div class='radio_group form_field force'>", "afterElement" => "</div>"));
		$this->form->addTextbox("first_name", "First name", array("required" => true));
		$this->form->addTextbox("last_name", "Last name", array("required" => true));
		$this->form->addEmail("email", "Email", array("required" => true));
		$this->form->addHoneypot();

		$this->form->addSubmit("submit", "Build it!", array("attr" => array("class" => "btn")));
	}

	protected function getTopicsOptions()
	{
		if (is_null($this->http)) {
			return false;
		}

		 $this->http->setEndpoint("topics")
			->addQueryStringParam("per_page", -1);

        $response = $this->http->get();

        $options = array();
        foreach ($response->_embedded->topics as $topic) {
        	$options[$topic->slug] = $topic->name;
        }

        return $options;
	}

	protected function validateForm()
	{
		if ($this->form->isValid()) {

			if ($this->form->passedHoneypot()) {

				$this->saveInDatabase();

				// compile the code
				$code = htmlspecialchars($this->compileCode());
				$this->code = "<pre><code class='html'>{$code}</code></pre>";

	        } else {
	            // we have a bot -- do nothing
	        }
		}
	}

	protected function saveInDatabase()
	{
		$topics = isset($this->requestParams["topics"]) ? $this->requestParams["topics"] : array();

		$this->tableName = "form_widget";
		return $this->create(array(
			"name" => $this->requestParams["first_name"] . " " . $this->requestParams["first_name"],
			"email" => $this->requestParams["email"],
			"title" => $this->requestParams["title"],
			"count" => $this->requestParams["count"],
			"topics" => serialize($topics),
			"tags" => serialize($this->requestParams["tags"])
		));
	}

	public function compile()
	{
		return array(
			"form" => $this->form->render(),
			"code" => $this->code
		);
	}

	/**
	 * This code is designed to work on production, where
	 * DEBUG is false.
	 */
	protected function makeApiKey($request)
	{
		// make user
		$user = $this->http->clearPrevious()
			->setEndpoint("users/create")
			->addQueryStringParam("role", 2)
			->addQueryStringParam("first_name", $this->requestParams["first_name"])
			->addQueryStringParam("last_name", $this->requestParams["last_name"])
			->addQueryStringParam("email", $this->requestParams["email"])
			->addQueryStringParam("affiliation", "Hub widget")
			->post();

		if (property_exists($user, "error") && $user->message == "User already exists.") {

			// User already exists, fetch that info
			$user = $this->http->clearPrevious()
				->setEndpoint("users")
				->addQueryStringParam("email", $this->requestParams["email"])
				->get();
			$userid = $user->_embedded->users[0]->id;

		} else {

			// user that was created
			$userid = $user->results->id;
		}

		// make app
		$app_name = urlencode("Hub widget");
		$app = $this->http->clearPrevious()
			->setEndpoint("users/{$userid}/apps/create")
			->addQueryStringParam("app_name", $app_name)
			->post();

		if (property_exists($app, "error") && $app->message == "App already exists.") {

			// STUCK HERE
			
			// App already exists, fetch that info
			$app = $this->http->clearPrevious()
				->setEndpoint("users/{$userid}/apps")
				->addQueryStringParam("app_name", $app_name)
				->get();

			$appkey = $app->_embedded->apps[0]->api_key;
		} else {
			$appkey = $app->results->api_key;
		}

		return $appkey;
	}

	protected function compileCode()
	{
		$html = "";


		// theme
		$theme = $this->requestParams["theme"];

		if ($theme == "light") {
			$html .= "<link rel=\"stylesheet\" href=\"http://hub.jhu.edu/assets/shared/css/widget-light.css\" />\n";
		} else if ($theme == "dark") {
			$html .= "<link rel=\"stylesheet\" href=\"http://hub.jhu.edu/assets/shared/css/widget-dark.css\" />\n";
		}


		// attributes
		$attrs = array();

		if (!empty($this->requestParams["title"])) {
			$attrs["title"] = $this->requestParams["title"];
		}

		if (!empty($this->requestParams["count"])) {
			$attrs["count"] = $this->requestParams["count"];
		}

		if (!empty($this->requestParams["tags"])) {
			$attrs["tags"] = str_replace(" ", "", $this->requestParams["tags"]);
		}

		if (!empty($this->requestParams["topics"])) {
			$attrs["topics"] = implode(",", $this->requestParams["topics"]);
		}

		// make an API key
		$attrs["key"] = $this->makeApiKey($this->requestParams);

		// get the current API version
		$attrs["version"] = 0;

		$html .= "<div id=\"hubWidget\"";

		foreach ($attrs as $k => $v) {
			$html .= " {$k}=\"{$v}\"";
		}

		$html .= "></div>\n";



		// script
		$html .= "<script src=\"http://hub.jhu.edu/assets/shared/js/hubwidget.2.1.min.js\"></script>";

		return $html;

	}
}