<?php 

namespace app\controllers;
use app\models\Pages;
use \app\base\View;

class PagesController extends \app\base\Controller
{
    protected $objectName = "Pages";

    public function __construct($action, $options)
    {
        parent::__construct($action, $options);
    }

    /**
     * Analyzes the $options["page"] variable to determine if this
     * type of page has its own model apart from the base Pages model.
     * @return string Path to model to instantiate
     */
    protected function getModelName()
    {
        if (isset($this->options["page"])) {
            $page = ucfirst($this->options["page"]);
            $class = "\\app\\models\\pages\\" . $page;
            if (class_exists($class)) {
                return $class;
            }
        }
        
        return "\\app\\models\\" . $this->objectName;
    }

    public function index()
    {
        $this->model->findAll();
        $this->model->sortPages();
        $this->model->createPageGroups();

        $this->model->data["page_title"] = "Pages";
        $this->model->data["section"] = "pages";
        $this->render("pages/index");
    }

    public function edit()
    {
        $params = $this->router->request()->params();

        $this->model->queryById($this->id);

        if (isset($params["cancel"])) {
            $this->router->redirect("/manager/pages", 302);
        }
        
        if (isset($params["submit"])) {
            $this->model->processEditForm($this->id, $params);
        }

        $this->model->data["page_title"] = "Edit Page";
        $this->model->data["section"] = "pages";

        $this->render("pages/edit");
    }

    public function show()
    {
        // call pages model
        $this->model->getPageData();
        $this->model->setTemplate();

        // populate view
        $this->render($this->model->data["template"]);
    }
}