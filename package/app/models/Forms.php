<?php

namespace app\models;

class Forms extends Model
{
	/**
	 * Form maker object
	 * @var object
	 */
	protected $form;

	/**
	 * Form ID
	 * @var string
	 */
	protected $formId;

	/**
	 * Router object
	 * @var object
	 */
	protected $router;

	/**
	 * Mailer object
	 * @var object
	 */
	protected $mailer;

	/**
	 * Request parameters
	 * @var array
	 */
	protected $requestParams = array();

	/**
	 * HTTP object
	 * @var object
	 */
	protected $http;

	/**
	 * Form config
	 * @var array
	 */
	protected $config = array();

	/**
	 * Code the user will need to display
	 * the widget on their website.
	 * @var string
	 */
	protected $code;


	public function __construct($formId, $router, $http = null)
	{
		parent::__construct();

		$this->form = new \HtmlForm\Form($this->config);
        $this->mailer = new \app\workers\Mailer();

		$this->formId = $this->formatId($formId);
		$this->router = $router;
		$this->http = $http;

		$this->make();

		$this->requestParams = $this->router->request()->params();

		if (isset($this->requestParams["submit"])) {
			$this->validateForm();
		}
	}

	/**
	 * Take the form ID passed in through the manager
	 * and format it property for PHP.
	 * 
	 * @param  string $formId 
	 * @return string
	 */
	protected function formatId($formId)
	{
		return str_replace("-", "_", $formId);
	}

	/**
	 * Adds form fields on the form object.
	 * Taken care of in child classes.
	 * @return [type] [description]
	 */
	protected function make()
	{

	}

	/**
	 * Default validation
	 * @return [boolean] TRUE if form passes validation;
	 *                   FALSE if fails validation
	 */
	protected function validateForm()
	{
		return $this->form->isValid();
	}

	/**
	 * Default form compilation
	 * @return string Form HTML
	 */
	public function compile()
	{
		return $this->form->render();
	}

}