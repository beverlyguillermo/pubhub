<?php 

namespace app\controllers;
use app\models\Tweets;
use \app\base\View;

class TweetsController extends \app\base\Controller
{
	protected $objectName = "Tweets";

    public function __construct($action, $options)
    {
        parent::__construct($action, $options);
    }

    public function call()
    {
        $this->model->getTweets();
        $this->render("tweets/list");
    }
}
