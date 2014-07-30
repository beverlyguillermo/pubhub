<?php

namespace app\models\pages;

class Taxonomies extends \app\models\Pages
{
	public function __construct($options = array())
	{
		parent::__construct($options);
	}

	public function getPageData()
	{
		$response = $this->http
            ->setEndPoint("articles")
            ->addQueryStringParam($this->vocabulary, $this->term)
            ->addQueryStringParam("per_page", 15)
            ->addQueryStringParam("page", $this->page_number)
            ->get();

        if (!empty($response->_embedded->articles)) {

            // get pretty name of taxonomy term
            $termQuery = $this->http
                ->setEndPoint($this->vocabulary)
                ->addQueryStringParam($this->vocabulary, $this->term)
                ->get();

            $vocab = $this->vocabulary;

            $term = array_shift($termQuery->_embedded->$vocab);

            $this->data["payload"] = $response->_embedded->articles;
            $this->data["vocabulary"] = $this->vocabulary;
            $this->data["term"] = $this->term;
            $this->data["current_page"] = $this->page_number;
            $this->data["page_title"] = $term->name;

        } else {
            $this->router->notFound();
            $this->log->addError("Triggering 'notFound' from " . __FILE__ . ":" . __LINE__, array(json_encode($response)));
        }
	}

    public function setTemplate()
    {
        if ($this->vocabulary == "locations") {
            $this->data["template"] = "pages/hub/location";
        } else {
            $this->data["template"] = "pages/hub/taxonomy";
        }
    }
}