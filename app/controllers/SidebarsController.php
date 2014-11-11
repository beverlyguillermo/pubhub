<?php 

namespace app\controllers;
use app\models\Sidebars;
use \app\base\View;

class SidebarsController extends \app\base\Controller
{
    protected $objectName = "Sidebars";

    public function __construct($action, $options)
    {
        parent::__construct($action, $options);
    }

    public function index()
    {
        $this->model->findAll();
        
        $this->model->data["page_title"] = "Sidebars";
        $this->model->data["section"] = "sidebars";
        
        $this->render("sidebars/index");
    }

    public function edit()
    {
        $params = $this->router->request()->params();

        $this->model->queryById($this->id);

        if (isset($params["cancel"])) {
            $this->router->redirect("/manager/sidebars", 302);
        }
        
        if (isset($params["submit"])) {
            $this->model->processEditForm($this->id, $params);
        }

        $this->model->data["page_title"] = "Edit Sidebar";
        $this->model->data["section"] = "sidebars";

        $this->render("sidebars/edit");
    }

    public function show()
    {
        // call sidebars model
        $this->model->getSidebarData();
        $this->model->setTemplate();

        // populate view
        $this->render($this->model->data["template"]);
    }

    public function create()
    {
        $params = $this->router->request()->params();

        $this->model->data["page_title"] = "Create new sidebar";
        $this->model->data["section"] = "sidebars";

        if (isset($params["submit"])) {
            $this->model->createSidebar($params);
        }

        $this->render("sidebars/create");
    }
}
