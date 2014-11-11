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

class Sidebars extends Model
{
  protected $tableName = "sidebars";

  /**
   * Data provided by the model
   * @var array
   */
  public $data = array();

  protected $sidebarColumns = array(
        "id",
        "name",
        "html"
    );

  public function __construct($options = array())
  {
    parent::__construct($options);
  }

  /**
   * Find all records in this model's database table. Sorts
   * the results by the 'name' field ascending.
   * @return self
   */
  public function findAll()
  {
    $sql = "SELECT * FROM {$this->tableName} WHERE deleted = 0 ORDER BY name ASC";

    $this->pdo->prepared = $this->pdo->prepare($sql);
    $this->pdo->prepared->execute();

    $this->data["sidebars"] = $this->pdo->prepared->fetchAll(PDO::FETCH_ASSOC);

    return $this;
  }

  /**
   * Returns the sidebar with the given ID
   * @param  $id integer Sidebar ID
   * @return self
   */
  public function queryById($id)
  {
    // get active page data
    $fields = array("id" => $id);
    $sidebar_data = parent::findByField($fields);
    $this->data["sidebar_data"] = array_shift($sidebar_data);

    return $this;
  }

  public function createSidebar($values)
  {
    $values = $this->sanitizeValues($values);

    $new = $this->create($values);

    $name = $values["name"];
    Messages::push("success", "The {$name} sidebar was successfully created. Whoo hoo!");

    $this->router->redirect("/manager/sidebars");
  }

  public function processEditForm($id, array $values)
  {
    $values = $this->sanitizeValues($values);

    $this->update($id, $values);

    $name = $this->data["sidebar_data"]["name"];
    Messages::push("success", "The {$name} sidebar was successfully updated. Good job!");

    $this->router->redirect("/manager/sidebars");
  }

  /**
     * Sanitize values submitted to the form for
     * update in the database.
     * @return  null
     */
    protected function sanitizeValues($values)
    {
      unset($values["submit"]);
      
      return $values;
    }

}
