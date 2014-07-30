<?php

namespace app\adapters\interfaces;

interface TemplateEngine
{
	public function render($path, $data);
}