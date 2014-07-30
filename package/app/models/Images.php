<?php

namespace app\models;
use \app\workers\Router;

class Images extends Model
{
	protected $tableName = "images";

	public $data = array();

	public function __construct($options)
	{
		parent::__construct($options);

		$this->data["section"] = "image";
	}


	public function findById()
	{
		$response = $this->http->setEndpoint("images/{$this->id}")
			->get();

		if (isset($response->error)) {
			$this->router->notFound();
		}

		$this->data["results"] = $response;
	}

	public function findGalleryTitle()
	{
		$response = $this->http->setEndpoint("galleries/{$this->gid}")
			->get();

		$this->data["gallery"] = !isset($response->error) ? $response : null;
	}
	

    public function setTemplate()
    {
    	$this->data["template"] = "images/image";
    }
}