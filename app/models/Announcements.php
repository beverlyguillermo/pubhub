<?php

namespace app\models;
use app\models\Issues;
use \app\workers\Router;
use \app\workers\Messages;
use \app\workers\Database;
use \PDO;

class Announcements extends Model
{
	protected $tableName = "announcements";

	public $data = array();
	protected $preview = false;

	public function __construct($options)
	{
		parent::__construct($options);
		$this->data["section"] = "announcement";
		$this->data["preview"] = $this->preview;
	}

	public function findByCriteria()
	{
		// Get the announcement data
		$this->http->setEndpoint("announcements")
			->addQueryStringParam("slug", $this->slug);

		// Enable preview, if necessary
		if ($this->preview) {
			$this->http->addQueryStringParam("preview", true);
		}

		$response = $this->http->get();

		if (!empty($response->_embedded->announcements)) {
			$this->data["results"] = array_shift($response->_embedded->announcements);
		
		} else {
			$this->router->notFound();
			$this->log->addError("Triggering 'notFound' from " . __FILE__ . ":" . __LINE__, array(json_encode($response)));
		}
	}

	/**
     * Based on the options passed from the router, figure out which
     * template and layout to use.
     *
     */
    public function setTemplate()
    {
    	$this->data["layout"] = "hub";
		$this->data["template"] = "announcements/announcement";
    }
}