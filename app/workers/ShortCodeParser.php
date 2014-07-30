<?php

namespace app\workers;

class ShortCodeParser
{
	protected $router;
	protected $http;

	public function __construct($router, $http)
	{
		$this->router = $router;
		$this->http = $http;
	}

	public function process($html)
	{
		$matches = array();
        preg_match_all("/\[([a-z]+)([^\]]*)\]/i", $html, $matches);

        foreach ($matches[1] as $key => $type) {

            $methodName = "parse__" . $type;
            if (method_exists($this, $methodName)) {
                $parsed = $this->$methodName($matches[2][$key]);
                $html = str_replace($matches[0][$key], $parsed, $html);
            }
        }

        return $html;
	}

	protected function parse__form($details)
    {
    	$matches = array();
    	preg_match_all("/\id=[\"']([a-z0-9]+)[\"']/i", $details, $matches);
    	
    	if (empty($matches[1])) {
    		return;
    	}

    	$formId = array_pop($matches[1]);

        $form = new \app\models\Forms(new \HtmlForm\Form(), $formId, $this->router, $this->http);
        
        return $form->compile();
    }
}