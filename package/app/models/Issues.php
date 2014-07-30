<?php

namespace app\models;
use \app\workers\Router;
use \app\workers\Messages;
use \app\workers\Database;
use \PDO;

class Issues extends Model
{
	protected $tableName = "issues";

	public $data = array();
	public $per_page = 5;

	public function __construct($options)
	{
		parent::__construct($options);

		if (isset($this->year) && isset($this->edition)) {
			$this->data["section"] = "contents";
		} else {
			$this->data["section"] = "homepage";
		}
		$this->data["preview"] = $this->preview;
	}


	public function findByCriteria()
	{
		// Start building the API call
		$this->http->setEndpoint("issues")
			->addQueryStringParam("source", $this->source);

		// Enable preview, if necessary
		if ($this->preview) {
			$this->http->addQueryStringParam("preview", true);
		}

		switch ($this->data["section"]) {
			case "homepage":
				$this->issueIndex();
				break;

			case "contents":
				$this->issueContents();
				break;
		}
	}


	protected function issueIndex()
	{
		// Add index-specific params
		$this->http->addQueryStringParam("per_page", $this->per_page);

		// Make the API call
		$response = $this->http->get();
		$issues = $response->_embedded->issues;

		if (empty($issues)) {
			return;
		}

		$features_to_get = array();

		foreach ($issues as &$issue) {

			// Get the cover story if necessary
			if (!empty($issue->_links->cover_story)) {
				$endpoint = $this->extractLinkFromObject($issue->_links->cover_story);
				$this->http->setEndpoint($endpoint);

				// Enable preview, if necessary
				if ($this->preview) {
					$this->http->addQueryStringParam("preview", true);
				}

				$issue->_links->cover_story = $this->http->get();
			}

			if (!empty($issue->_links->web_features)) {
				$features = $this->extractLinksFromObjects($issue->_links->web_features);
				$issue->_links->web_features = array_map(array($this, "parseURIList"), $features);
				$features_to_get = array_merge($features_to_get, $issue->_links->web_features);
			}
		}


		// Get and stitch the web features
		if (!empty($features_to_get)) {
			$ids = implode(",", $features_to_get);

			$this->http->setEndpoint("articles")
				->addQueryStringParam("ids", $ids)
				->addQueryStringParam("order_by", "list")
				->addQueryStringParam("per_page", count($features_to_get));

			// Enable preview, if necessary
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
			foreach ($issues as &$issue) {

				if (!empty($issue->_links->web_features)) {
					foreach ($issue->_links->web_features as &$feature_article) {
						$id = $feature_article;
						$feature_article = !empty($newArticles[$id]) ? $newArticles[$id] : null;
					}
				}
			}
		}

		$this->data["results"] = $issues;
	}


	public function findMeta()
	{
		// Start building the API call
		$this->http->setEndpoint("issues")
			->addQueryStringParam("source", $this->source);

		// Enable preview, if necessary
		if ($this->preview) {
			$this->http->addQueryStringParam("preview", true);
		}

		// Add edition-specific params
		$this->http->addQueryStringParam("year", $this->year)
			->addQueryStringParam("edition", $this->edition);

		// Make the API call
		$issue = $this->http->get();

		if (empty($issue->_embedded->issues)) {
			$this->router->notFound();
			$this->log->addError("Triggering 'notFound' from " . __FILE__ . ":" . __LINE__, array(json_encode($issue)));
		}

		$issue = array_shift($issue->_embedded->issues);

		return $issue;
	}


	protected function issueContents()
	{
		$issue = $this->findMeta();

		// Get the articles resource
		$this->http->setEndpoint($this->extractLinkFromObject($issue->_links->articles))
			->addQueryStringParam("per_page", 100)
			->addQueryStringParam("order_by", "page_number|asc");

		// Enable preview, if necessary
		if ($this->preview) {
			$this->http->addQueryStringParam("preview", true);
		}

		$articles = $this->http->get();

		if (!empty($articles->_embedded->articles)) {
			$issue->contents = $this->tableOfContents($articles->_embedded->articles);
		}


		$this->data["results"] = $issue;
	}


	protected function tableOfContents($articles)
	{
		$contents = array();
		$parents = array();

		foreach ($articles as $article) {

			$department = $article->_embedded->departments;

			if (empty($department)) {
				continue;
			}

			$department = array_shift($department);

			$departmentName = addslashes($department->name);

			if (!empty($department->parent)) {
				$parentName = addslashes($department->parent->name);
				$contents[$parentName][] = $article;

			} else {
				$contents[$departmentName][] = $article;
			}

		}

		return $contents;
	}
	

	/**
     * Based on the options passed from the router, figure out which
     * template to use.
     *
     */
    public function setTemplate()
    {
    	// template base
    	$this->data["template"] = "issues/{$this->source}/";

		if (isset($this->year) && isset($this->edition)) {
			$this->data["template"] .= "contents";
		}

		else {
			$this->data["template"] .= "index";
		}
    }
}