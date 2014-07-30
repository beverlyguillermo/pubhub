<?php

namespace app\models;

class Model extends \app\base\Model
{
	protected $log;

	public function __construct($options = array())
	{
		$this->log = \app\workers\Logger::getInstance();
		parent::__construct($options);
	}

	public function parseURIList($uri)
	{
		$parts = explode("/", $uri);
		return array_pop($parts);
	}

	public function extractLinksFromObjects($objects)
	{
		return array_map(function($a) {
			return ltrim($a->href, "/");
		}, $objects);
	}

	public function extractLinkFromObject($object)
	{
		if (is_array($object)) {
			$object = array_shift($object);
		}
		return ltrim($object->href, "/");
	}
}