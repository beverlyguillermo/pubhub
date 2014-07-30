<?php

namespace app\models;
use app\models\Issues;
use \app\workers\Router;
use \app\workers\Messages;
use \app\workers\Database;
use \PDO;

class Articles extends Model
{
	protected $tableName = "articles";

	public $data = array();
	protected $preview = false;

	public function __construct($options)
	{
		parent::__construct($options);
		$this->data["section"] = "article";
		$this->data["preview"] = $this->preview;
	}

	public function findByCriteria()
	{
		// Get the article data
		$this->http->setEndpoint("articles")
			->addQueryStringParam("source", $this->source)
			->addQueryStringParam("slug", $this->slug);

		switch ($this->source) {
		    case "hub":
		    case "summary":
		        $this->http->addQueryStringParam("publish_date", $this->year . "-" . $this->month . "-" . $this->day);
		        break;

		    case "magazine":
		    case "gazette":
				$this->http->addQueryStringParam("edition", $this->edition);
				$this->http->addQueryStringParam("year", $this->year);
				break;
		    break;
		}

		// Enable preview, if necessary
		if ($this->preview) {
			$this->http->addQueryStringParam("preview", true);
		}

		$response = $this->http->get();

		if (!empty($response->_embedded->articles)) {

			//$this->log->addInfo("Testing log from " . __FILE__ . ":" . __LINE__, array(json_encode($response)));

			$results = array_shift($response->_embedded->articles);

			$results->_links->galleries = !empty($results->_links->galleries) ? $this->getGalleries($results->_links->galleries) : null;
			$results->_links->related_content = !empty($results->_links->related_content) ? $this->getRelatedContent($results->_links->related_content) : null;

			// if ($this->source == "magazine" || $this->source == "gazette") {
			// 	$results->publication_content->department_set = $this->getDepartmentSet($results->publication_content->department_set);
			// }

			$this->data["results"] = $results;
		
		} else {
			$this->router->notFound();
			$this->log->addError("Triggering 'notFound' from " . __FILE__ . ":" . __LINE__, array(json_encode($response)));
		}


		// Get issue data, if needed

		if ($this->source == "magazine" || $this->source == "gazette") {
			$options = array(
				"source" => $this->source,
				"year" => $this->year,
				"edition" => $this->edition,
				"preview" => $this->preview
			);
			$issue = new Issues($options);
			$this->data["issue"] = $issue->findMeta();

			// Get and stitch the carousel
			
			if (!empty($this->data["issue"]->_links->carousel)) {
				$carousel = $this->extractLinksFromObjects($this->data["issue"]->_links->carousel);
				$this->data["issue"]->_links->carousel = $features_to_get = array_map(array($this, "parseURIList"), $carousel);
			}

			if (!empty($features_to_get)) {
				$ids = implode(",", $features_to_get);

				$this->http->setEndpoint("articles")
					->addQueryStringParam("ids", $ids)
					->addQueryStringParam("per_page", 100);

				if ($this->preview) {
					$this->http->addQueryStringParam("preview", true);
				}

				$articles = $this->http->get();
				$articles = $articles->_embedded->articles;


				// Revise the keys of the recordset to be the ID of the contained
				// object and not just 0, 1, 2, 3, etc...
				$newArticles = array();
				foreach ($articles as $key => $record) {
					$newArticles[$record->id] = $record;
				}

				// Stitch the web features
				foreach ($this->data["issue"]->_links->carousel as &$feature_article) {
					$id = $feature_article;
					$feature_article = !empty($newArticles[$id]) ? $newArticles[$id] : null;
				}
			}
			
		}
	}

	public function getGalleries($galleries)
	{
		if (empty($galleries)) {
			return $galleries;
		} else {

			$galleries = $this->extractLinksFromObjects($galleries);

			// Make an HTTP request for the galleries
			$ids = array_map(array($this, "parseURIList"), $galleries);
			$this->http->setEndpoint("galleries")
				->addQueryStringParam("ids", implode(",", $ids));

			// Enable preview, if necessary
			if ($this->preview) {
				$this->http->addQueryStringParam("preview", true);
			}

			$results = $this->http->get();
			return $results->_embedded->galleries;
		}
	}

	public function getRelatedContent($related)
	{
		if (empty($related)) {
			return $related;
		
		} else {

			$related = $this->extractLinksFromObjects($related);

			// Make an HTTP request for these contents
			$ids = array_map(array($this, "parseURIList"), $related);
			$this->http->setEndpoint("articles")
				->addQueryStringParam("ids", implode(",", $ids));

			// Enable preview, if necessary
			if ($this->preview) {
				$this->http->addQueryStringParam("preview", true);
			}

			$results = $this->http->get();
			return $results->_embedded->articles;
		}
	}

	/**
     * Based on the options passed from the router, figure out which
     * template and layout to use.
     *
     */
    public function setTemplate()
    {
    	// base
    	$this->data["template"] = "articles/{$this->source}/";
    	$this->data["layout"] = $this->source;

		if ($this->source == "magazine") {

			if (!empty($this->data["results"]->_embedded->departments[0]->parent->slug)) {
				$template = $this->data["results"]->_embedded->departments[0]->parent->slug;
			
			} else if (!empty($this->data["results"]->_embedded->departments[0]->slug)) {
				$template = $this->data["results"]->_embedded->departments[0]->slug;
			} else {
				$template = false;
			}

			// find parent

			if ($template) {
				$this->data["template"] .= "sub/" . $template;
			}
			else {
				// log this
				$this->data["template"] .= "single";
			}
		}

		else if ($this->source == "gazette") {
			$this->data["template"] .= "single";
		}

		// hub
		else {

			switch ($this->data["results"]->format) {
				case "Video Emphasis":
					$this->data["template"] .= "video";
					break;

				case "Gallery Emphasis":
					$this->data["template"] .= "gallery";
					break;

				// "Impact Image Emphasis" and no format
				default:
					$this->data["template"] .= "impact";
					break;
			}
		}
    }

	protected function createSlug($string)
	{
		$slug = strip_tags($string);
		$slug = preg_replace("/[^a-zA-Z0-9\-\s]+/", "", $slug);
		$slug = preg_replace("/[\-\s]{2,}/", "-", $slug);
		return strtolower(str_replace(" ", "-", $slug));
	}

}