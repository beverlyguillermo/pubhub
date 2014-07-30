<?php 

namespace app\controllers;

class HubstagramController extends \app\base\Controller
{
    protected $objectName = "Hubstagram";

    protected $tags = array(
        "#jhu",
        "#johnshopkinsuniversity",
        "#peabodyinstitute",
        "#instajhu",
        "#embracethes"
    );

    protected $tagsNeedingFiltering = array(
        "#hopkins",
        "#johnshopkins"
    );

    protected $locations = array(
        "73956",    // The Johns Hopkins University
        "6224511",  // Peabody Institute
        "6224511",  // Peabody Institute Library
        "972604",   // George Peabody Library
        "2245788"   // Gilman Hall
    );

    protected $filters = array(
        "johnshopkins", "jhu", "johnshopkinsuniversity",
        "lacrosse", "lax", "football", "basketball", "ncaa",
        "baltimore", "maryland"
    );

    public function __construct($action, $options)
    {
        $this->options = $options;
        parent::__construct($action, $options);
    }

    protected function setData()
    {
        $this->model->addFilters($this->filters)
            ->addTagStreams($this->tags)
            ->addTagStreams($this->tagsNeedingFiltering, true)
            ->addLocationStreams($this->locations)
            ->process();
    }

	public function show()
    {
        $this->setData();
        $this->model->sortData();
        $this->model->setTemplate();
        $this->render($this->model->data["template"]);
    }

    public function promote()
    {   
        // setup a default number to return if one isn't set in the request params
        $count = empty($this->options["count"]) ? 3 : $this->options["count"];

        $this->setData();
        $this->model->sortData(true, 3);
        
        // get more than the count requested, just in case some
        // of those photos were banned between caches.
        $this->model->data["media"] = array_slice($this->model->data["media"], 0, $count * 3);

        // Get a numeric array
        $this->model->data["media"] = array_values($this->model->data["media"]);

        $likedPhoto = $this->model->getRecentLikedPhoto();

        if ($likedPhoto) {

            // Get rid of the photo in the pool if it exists there
            $this->model->data["media"] = array_map(function($v) use ($likedPhoto) {
                if ($v->id == $likedPhoto->id) {
                    return;
                }
                return $v;
            }, $this->model->data["media"]);

            // Get rid of any empty values (caused by array_map())
            $this->model->data["media"] = array_filter($this->model->data["media"]);

            // Add the liked photo to the front of the pool
            array_unshift($this->model->data["media"], $likedPhoto);
        }

        // to json
        echo $this->toJSON($this->model->data); die();
        die();
    }

    public function manage()
    {
        $params = $this->router->request()->params();

        if (isset($params["cancel"])) {
            $this->router->redirect("/manager", 302);
        }
        
        if (isset($params["submit"]) && $params["submit"] == "ban") {
            $this->model->processBanForm($params);
        }

        $this->model->data["page_title"] = "Manage Hub/Pix";
        $this->model->data["section"] = "hubpix";

        $this->render("pages/hubpix");
    }

    protected function toJSON($content)
    {
        $output = json_encode($content);
        
        if (!empty($this->options["callback"])) {
            $callback = $this->options["callback"];
            $output = $callback . "(" . $output . ");";
        }
        return $output;
    }
}