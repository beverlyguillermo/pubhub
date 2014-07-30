<?php

namespace app\models;
use app\models\Versions;
use \app\workers\Messages;
use \app\workers\Router;
use \app\workers\Database;
use \app\workers\HTTP;
use \app\workers\ShortCodeParser;
use \Resty;
use \PDO;

class Pages extends Model
{
    protected $tableName = "pages";

    /**
     * Data provided by the model
     * @var array
     */
    public $data = array();

    /**
     * Base page requested through the router;
     * for example, "about" or "arts-culture"
     * @var string
     */
    public $page;

    /**
     * Array of subpages. For example; /developers/widget/something-else
     * would result in array("widget", "something-else")
     * @var array
     */
    public $subpages = array();

    /**
     * Values submitted to the form
     * @var array
     */
    protected $form_values = array();

    /**
     * The action to be taken with the edit form
     * @var string
     */
    protected $action;

    /**
     * Cleaned form values to set to the database
     * @var array
     */
    protected $updateValues = array();

    /**
     * Information about the page from the "Pages" DB table
     * @var array
     */
    protected $page_info = array();

    protected $versionColumns = array(
        "id",
        "template",
        "feature_queue",
        "rails",
        "tags",
        "html",
        "form",
        "locations"
    );


    public function __construct($options = array())
    {
        parent::__construct($options);

        $this->data["section"] = "page";

        // Get the full slug
        if (!empty($this->subpages)) {
            $this->page = $this->page . "/" . implode("/", $this->subpages);
        }

        // Slug is used for static pages. Some pages use the same template, but depending
        // on the slug, elements are placed in different areas (look at pages/hub/media.twig)
        $this->data["slug"] = $this->page;
    }

    /**
     * Returns an associative array of one page's
     * active and schedule versions.
     *
     * @param $id integer representing the ID of the
     * page whose data to retrieve
     */
    public function queryById($id)
    {
        // get active page data
        $fields = array("id" => $id);
        $page_data = parent::findByField($fields);
        $page_data = array_shift($page_data);

        // unserialize the page_features array
        $page_data["page_features"] = unserialize($page_data["page_features"]);

        // get versions
        $this->versions = new Versions();
        $this->versions->findVersions($id);

        // put it all together (do the hokey pokey)
        $this->data["page_data"] = $page_data;
        $this->data["versions"] = $this->versions->data;

        return $this->data;
    }

    /**
     * Gets information from the database on a particular page. Information
     * includes ID, page type, page title and endpoint (if applicable).
     *
     */
    protected function findPageBySlug()
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE LOWER(slug) LIKE LOWER('{$this->page}') AND section = '{$this->source}' AND deleted = 0";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        $page_info = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($page_info)) {
            $this->page_info = array_shift($page_info);

            // unserialize the page_features array
            $this->page_info["page_features"] = unserialize($this->page_info["page_features"]);
            
        } else {
            $this->router->notFound();
            $this->log->addError("Triggering 'notFound' from " . __FILE__ . ":" . __LINE__, array(json_encode($page_info)));
        }
    }

    /**
     * Find all records in this model's database table. Sorts
     * the results by the 'title' field ascending.
     */
    public function findAll()
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE deleted = 0 ORDER BY title ASC";

        $this->pdo->prepared = $this->pdo->prepare($sql);
        $this->pdo->prepared->execute();

        $pages = $this->pdo->prepared->fetchAll(PDO::FETCH_ASSOC);

        $versions = new Versions();

        foreach ($pages as &$page) {
            $page["active_version"] = $versions->findActiveVersion($page["id"]);
        }

        $this->data["pages"] = $pages;

        return $this->data;
    }

    /**
     * Sort pages alphabetically but move "Home" to the top
     * Rel: mananger
     * @return  array
     */
    function sortPages()
    {
        usort($this->data["pages"], function ($a, $b) {
            if ($a["type"] === "home") {
                return -1;
            }
            if ($b["type"] === "home") {
                return 1;
            }
            return strcmp($a["title"], $b["title"]);
        });
    }

    /**
     * Sorts pages into sections for the Manager Pages display
     * Rel: manager
     * @return  null
     */
    public function createPageGroups()
    {
        $groups = array(
            "topics" => array(),
            "hub" => array(),
            "magazine" => array(),
            "gazette" => array()
        );

        foreach($this->data["pages"] as $page) {
            $page = (object) $page;
            if (in_array($page->type, array("topics", "home"))) {
                $groups["topics"][] = $page;
            } elseif ($page->section === "magazine") {
                $groups["magazine"][] = $page;
            } elseif ($page->section === "gazette") {
                $groups["gazette"][] = $page;
            } else {
                $groups["hub"][] = $page;
            }
        }

        $this->data["page_groups"] = $groups;
    }


    /**
     * Processes the data submitted to the edit page form.
     * @return  null
     */
    public function processEditForm($page_id, array $values)
    {
        $this->tableName = "versions";

        $this->setProcessingVariables($page_id, $values);

        if ($this->action == "delete") {
            $this->delete($this->form_values["id"]);
            Messages::push("success", "The version was successfully deleted.");
            $this->router->redirect("/manager/pages");
        }

        $this->setValuesforUpdate($page_id);

        $title = $this->data["page_data"]["title"];

        if ($this->action == "live") {
            $this->create($this->updateValues);
            Messages::push("success", "The {$title} page was successfully edited. Good job!");

        } elseif ($this->action == "new") {
            $this->create($this->updateValues);
            Messages::push("success", "The {$title} page was successfully scheduled. Good job!");
        }

        elseif ($this->action == "update") {
            $this->update($this->form_values["id"], $this->updateValues);
            Messages::push("success", "The {$title} page was successfully edited. Good job!");
        }

        $this->router->redirect("/manager/pages");
    }

    /**
     * Sets up the submitted values from the form in a manner we can use them.
     * Also sets the action for this particular transaction.
     * @return  null
     */
    protected function setProcessingVariables($page_id, $values)
    {
        $this->action = $values["submit"];

        $columns = array_fill_keys($this->versionColumns, "");
        $this->form_values = array_intersect_key($values, $columns);
    }

    /**
     * Sets up the values to pass to the database for either an
     * insert or update. Different types of forms need different
     * data sent to the database, so a switch statement is used.
     * @return  null
     */
    protected function setValuesforUpdate($page_id)
    {
        $pageFeatures = $this->data["page_data"]["page_features"];

        if (in_array("feature_queue", $pageFeatures) && !empty($this->form_values["feature_queue"])) {
            $this->updateValues["feature_queue"] = serialize($this->form_values["feature_queue"]);
        }

        if (in_array("rails", $pageFeatures) && !empty($this->form_values["rails"])) {
            $this->updateValues["rails"] = serialize($this->form_values["rails"]);
        }

        if (in_array("tags", $pageFeatures) && !empty($this->form_values["tags"])) {
            $this->updateValues["tags"] = serialize($this->form_values["tags"]);
        }

        if (in_array("locations", $pageFeatures) && !empty($this->form_values["locations"])) {
            $this->updateValues["locations"] = serialize($this->form_values["locations"]);
        }

        if (in_array("html", $pageFeatures) && !empty($this->form_values["html"])) {
            $this->updateValues["html"] = $this->form_values["html"];
        }

        if (in_array("form", $pageFeatures) && !empty($this->form_values["form"])) {
            $this->updateValues["form"] = $this->form_values["form"];
        }

        if (!empty($this->form_values["template"])) {
            $this->updateValues["template"] = $this->form_values["template"];
        }

        if ($this->action == "new" || $this->action == "live") {

            $this->updateValues["page_id"] = $page_id;
            $this->updateValues["published"] = date('Y-m-d H:i:s');

            if (isset($this->form_values["published"]) && $this->form_values["published"] != "Now") {
                $date_to_publish = strtotime($this->form_values["published"]);
                $this->updateValues["published"] = date('Y-m-d H:i:s', $date_to_publish);
            }
        }

        elseif ($this->action == "update") {
            $this->updateValues["page_id"] = $page_id;
        }
    }

    
    /**
     * Orchestrates getting the correct data for the requested data.
     * @return null
     */
    public function getPageData()
    {
        $this->findPageBySlug();

        $this->versions = new Versions();
        $this->versions->findActiveVersion($this->page_info["id"]);

        $this->data["page_title"] = $this->page_info["title"];

        $pageFeaturesToSet = $this->page_info["page_features"] ? $this->page_info["page_features"] : array();

        if (in_array("feature_queue", $pageFeaturesToSet)) {
            $this->setFeatureQueue();
        }

        if (in_array("rails", $pageFeaturesToSet)) {
            $this->setRails();
        }

        if (in_array("html", $pageFeaturesToSet)) {
            $this->setHtml();
        }

        if (in_array("form", $pageFeaturesToSet)) {
            $this->setForm();
        }

        if ($this->versions->data["active"]["template"] === "issues") {
            $this->setPastIssues();
        }

        if ($this->page_info["type"] == "topics") {
            $this->setSupportingItems("articles");
            $filters = $this->getFilters("tags");
            $this->matchFiltersToContent("tags", $filters, $this->data["results"]["endpoint"]);
        }
    }


    /**
     * Set featue queue data to the data object. It is up
     * to the template to decide how many to use.
     */
    protected function setFeatureQueue()
    {
        $active = $this->versions->data["active"];
        
        if (empty($active["feature_queue"])) {
            return;
        }

        // Topics pages will have a specified endpoint -- should we use this to gather features?
        $endpoint = !empty($this->page_info["endpoint"]) ? $this->page_info["endpoint"] : "articles";

        $endpoint = explode("/", $endpoint);
        $collection = array_pop($endpoint);

        $this->http->setEndpoint($collection)
                    ->addQueryStringParam("ids", implode(",", $active["feature_queue"]))
                    ->addQueryStringParam("order_by", "list");

        $this->response = $this->http->get();

        if (!empty($this->response->_embedded->$collection)) {
            $this->data["featured"] = $this->response->_embedded->$collection;
        } else {
            $this->data["featured"] = null;
        }

    }


    /**
     * Set rail data to the data object. It is up
     * to the template to decide how many to use.
     */
    protected function setRails()
    {
        $active = $this->versions->data["active"];

        if (empty($active["rails"])) {
            return;
        }

        $versionGetter = new Versions();
        $pageGetter = new self();

        $topicRails = array();
        $otherRails = array();

        foreach ($active["rails"] as &$rail) {
            $rail["type"] = "page";
            $pageGetter->queryById($rail["page"]);

            $rail["page"] = $pageGetter->data["page_data"];
            $rail["page"]["active"] = $versionGetter->findActiveVersion((int) $rail["page"]["id"]);

            $rail["site_path"] = $rail["page"]["slug"];
            $topicRails[] = $rail;
        }

        
        $this->data["rails"] = $active["rails"];
        $this->data["rail_groups"] = array("topics" => $topicRails, "other" => $otherRails);
    }


    /**
     * Set HTML data to the data object
     */
    protected function setHtml()
    {
        $active = $this->versions->data["active"];
        $html = $active["html"];

        if (empty($html)) {
            return;
        }

        $parser = new ShortCodeParser($this->router, $this->http);
        $html = $parser->process($html);

        $this->data["html"] = $html;
    }


    protected function setForm()
    {
        $active = $this->versions->data["active"];
        $formId = $active["form"];

        $formName = preg_split("/(-|_)/", $formId);
        $formName = array_map(function($v) {
            return ucfirst($v);
        }, $formName);
        $formName = implode("", $formName);

        $formName = "app\\models\\forms\\{$formName}";

        $form = new $formName($formId, $this->router, $this->http);
        $this->data["form"] = $form->compile();
    }


    /**
     * Set past issue data to the data object
     */
    protected function setPastIssues()
    {
        // Start building the API call
        $this->http->setEndpoint("issues")
            ->addQueryStringParam("source", $this->page_info["section"])
            ->addQueryStringParam("per_page", -1);

        // Make the API call
        $this->response = $this->http->get();

        $this->data["issues"] = array();
        foreach ($this->response->_embedded->issues as $issue) {
            $this->data["issues"][date("Y", $issue->publish_date)][] = $issue;
        }

        $this->data["issues"] = array_map(function ($issue) { 
            return (array) $issue; 
        }, $this->data["issues"]);
    }


    protected function setSupportingItems($item = "articles", $num = 25)
    {
        $active = $this->versions->data["active"];

        $feature_queue = "";
        $feature_queue_chopped = array();
        if (!is_null($active["feature_queue"])) {
            $feature_queue_chopped = array_slice(array_unique($active["feature_queue"]), 0, 3);
            $feature_queue = implode(",", $feature_queue_chopped);
        }

        // So JavaScript knows which IDs not to include in the query
        $this->data["excluded_ids"] = $feature_queue;

        $this->http->setEndpoint("{$this->page_info["endpoint"]}")
            ->addQueryStringParam("per_page", $num);

        if ($feature_queue) {
            $this->http->addQueryStringParam("excluded_ids", $feature_queue);
        }

        $this->response = $this->http->get();

        // So JavaScript knows which endpoint to query
        $this->data["endpoint"] = $this->page_info["endpoint"];

        if (isset($this->response->_embedded->$item) && !empty($this->response->_embedded->$item)) {
            $this->data["results"]["endpoint"] = $this->response->_embedded->$item;
        } else {
            $this->router->notFound();
        }
    }


    /**
     * Get an array of filters set in the manager.
     * Note: works on embedded taxonomy terms only.
     * 
     * @param  string   $filter     Type of filter (tags, locations)
     * @param  boolean  $getAll     If no filters are designated in the manager,
     *                              default to retrieving all filters
     * @return null
     */
    protected function getFilters($filter = "tags", $getAll = true)
    {
        $active = $this->versions->data["active"];

        // the filters designated in the manager, if any
        $filtersFromManager = isset($active[$filter]) ? $active[$filter] : null;


        $response = $this->http->setEndpoint($filter);

        if (empty($filtersFromManager) && $getAll) {
            // get all
            $response->addQueryStringParam("per_page", 100);
        } else if (!empty($filtersFromManager)) {
            // get only the ones designated in the manager
            $response->addQueryStringParam("per_page", count($filtersFromManager))
                ->addQueryStringParam("ids", implode(",", $filtersFromManager))
                ->addQueryStringParam("order_by", "list");
        } else {
            return array();
        }


        // get data on filters from the API
        $response = $response->get();
        $filtersPayload = $response->_embedded->$filter;


        // make a nice array of only the filter data we need
        $filterData = array();
        foreach ($filtersPayload as $f) {
            $filterData[$f->id] = array(
                "name" => $f->name,
                "slug" => $f->slug,
                "count" => 0
            );
        }

        return $filterData;
    }


    /**
     * Matched filters against content items. Create a count in the
     * filters array that keeps track of how many content items are
     * within a particular filter. Add the slug of each filter to
     * each item's filterClasses property.
     * Note: works on embedded taxonomy terms only.
     * 
     * @param  string   $filterName     Name of filter
     * @param  array    $filters        Cleaned filters (like from getFilters())
     * @param  array    $content        Array of content (articles, events, etc...)
     * @param  bool     $parent         Whether to check parent term (if exists) for a match 
     * @return null
     */
    protected function matchFiltersToContent($filterName, $filters, &$content, $parent = false)
    {
        foreach ($content as $item) {

            if (!property_exists($item, "filterClasses")) {
                $item->filterClasses = array();
            }

            // if there are applicable filters on this piece of content
            if (!is_null($item->_embedded->$filterName)) {

                foreach ($item->_embedded->$filterName as $f) {

                    $match = false;

                    if (isset($filters[$f->id])) {
                        $match = $f;
                    } else if ($parent && !is_null($f->parent)) {
                        if (isset($filters[$f->parent->id])) {
                            $match = $f->parent;
                        }
                    }

                    if ($match) {
                        $filters[$match->id]["count"]++;
                        $item->filterClasses[] = $match->slug;
                    }
                    
                }
            }
        }

        // assign the data to the resultset
        $this->data["results"][$filterName] = $filters;
    }


    /**
     * Based on the options passed from the router, figure out which
     * template to use.
     *
     */
    public function setTemplate()
    {
        // The rest are in the manager database
        $this->data["layout"] = $section = $this->page_info["section"];
        $type = $this->page_info["type"];
        $template = $this->versions->data["active"]["template"];
        
        if ($this->page == "not-found" || $this->page_info["section"] == "hub" && $this->page_info["type"] == "static") {

            if ($template) {
                $this->data["template"] = "pages/{$section}/{$template}";
                return;
            }
            $this->data["template"] = "pages/{$section}/{$type}";

        } else {

            if ($template && $this->page_info["type"] != "home" && $this->page_info["type"] != "topics") {
                $this->data["template"] = "pages/{$this->source}/{$template}";
                return;
            }
            $this->data["template"] = "pages/{$this->source}/{$this->page_info["type"]}";
        }
    }

}