<?php

namespace app\models\pages;

class Media extends \app\models\Pages
{
	public function __construct($options = array())
	{
		parent::__construct($options);
	}

	public function getPageData()
	{
		parent::getPageData();

		$this->feed = new \app\models\feeds\Media(array());
    $items = ($this->page_info["slug"] === "media/latest") ? 15 : 5;
    $this->data["feed"] = $this->feed->getData($items);
	}
}
