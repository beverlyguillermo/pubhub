<?php

namespace app\models\feeds;

class Tag extends \app\models\Feeds
{
  public function __construct($options)
    {
      parent::__construct($options);
    }


  public function getData($count = 20, $exclude = array())
  {
    $title = "Hub";
    $description = "Headlines from the Johns Hopkins news network";
    $image = array(
      "url" => $this->getBaseUrl() . "/assets/img/hub-logo-rss.png",
      "title" => "Hub",
      "link" => $this->getBaseUrl() . ""
    );
    $link = $this->getBaseUrl() . "/{$this->tag}";

    $endpoint = $this->getEndpoint($this->tag);
    $response = $this->http->setEndpoint($endpoint)
          ->addQueryStringParam("per_page", $count)
          ->addQueryStringParam("excluded_ids", implode(",", $exclude))
          ->get();

    return $response->_embedded->articles;
  }

  protected function createChannel()
  {
    $channel = parent::createChannel();
    $channel["link"] = $this->getBaseUrl() ."/{$this->tag}";

    return $channel;
  }

  protected function getEndpoint($slug)
    {
      $response = $this->http->setEndpoint("tags")
        ->addQueryStringParam("tags", $slug)
        ->get();

    if (empty($response->_embedded->tags)) {
        $this->router->notFound();
      }

      return $response->_embedded->tags[0]->_links->articles->href;
    }
}
