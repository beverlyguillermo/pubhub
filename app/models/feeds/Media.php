<?php

namespace app\models\feeds;

class Media extends \app\models\Feeds
{

    // Feeds to get on Media page
    protected $jhuFeeds = array(
            
        // Public Health
        array(
            "baseUrl" => "http://feeds.feedburner.com/",
            "endpoint" => "JHSPHNews"
        ),

        // Releases
        array(
            "baseUrl" => "http://releases.jhu.edu/",
            "endpoint" => "feed/"
        ),
        
        // Nursing News
        array(
            "baseUrl" => "http://nursing.jhu.edu/",
            "endpoint" => "news-events/news/news?feed=rss"
        ),

        // Medicine
        array(
            "baseUrl" => "http://www.hopkinsmedicine.org/",
            "endpoint" => "rss/HopkinsRSS.xml"
        )
    );

	public function __construct($options)
    {
    	parent::__construct($options);
    }

    protected function createChannel()
    {
        $channel = parent::createChannel();
        $channel["description"] = "News feeds from around Johns Hopkins";
        $channel["lastBuildDate"] = (int) time();

        return $channel;
    }

    /**
	 * Compile the huge JHU feed
	 * @param  integer $count Number of items to return
	 * @return null
	 */
	public function getData($count = 20)
	{
		$cacheKey = "media-jhu-feed-{$count}";

    	if ($feed = $this->cache->fetch($cacheKey)) {
            return $feed;
		}

        $collectedFeeds = array(); 

        // parse out each feed
        foreach ($this->jhuFeeds as $feed) {

            $xmlObj = $this->http->clearPrevious()
                ->setBaseURL($feed["baseUrl"])
                ->setEndpoint($feed["endpoint"])
                ->get();

            // This will trigger if the response comes back as a string. There was a bug in the HTTP worker
            // that was causing this to happen. This bug has since been fixed, but I am leaving this here
            // just in case. -jw
            if (!is_object($xmlObj) || !is_object($xmlObj->channel) || !is_object($xmlObj->channel->item)) {
	            // log
	            continue;
            }

            // get some info for the whole feed
            $source = (string) $xmlObj->channel->title;
            $linkToSource = (string) $xmlObj->channel->link;

            // parse out each item
            foreach ($xmlObj->channel->item as $item) {

            	$desc = !empty($item->description) ? (string) strip_tags($item->description, "<a><b><strong><i><em>") : "";
            	$desc = $this->truncate($desc, 150, " ");

            	$headline = (string) $item->title;

            	// Strip the date off the end of Medicine headlines
            	$headline = preg_replace("/\s-\s\d{1,2}\/\d{1,2}\/\d{2}/", "", $headline);

                $data = array(
                    "headline" => $headline,
                    "link" => (string) $item->link,
                    "publish_date" => strtotime($item->pubDate),
                    "description" => $desc,
                    "source" => array(
                        "name" => $source,
                        "url" =>$linkToSource
                    )
                );

                $collectedFeeds[] = $data;
            }

        }

        // sort the feed items by descending date
        usort( $collectedFeeds, function ($a, $b) {
          $a_val = $a["publish_date"];
          $b_val = $b["publish_date"];
          if ( $a_val == $b_val ) {
            return 0;
          }
          return ($a_val > $b_val) ? -1 : 1;
        });

        // limit the feed to designated number
        $finalFeed = array_slice($collectedFeeds, 0, $count);

		$items = array();

		// Grab only the data we need and sanitize
		foreach ($finalFeed as $item) {

			$items[] = array(
				"title" => htmlspecialchars($item["headline"]),                         
				"link" => $item["link"],
				"pubDate" => $item["publish_date"],
				"description" => $item["description"],
				"source" => array(
					"name" => $item["source"]["name"],
					"url" => $item["source"]["url"]
				)
			);
		}

		$this->cache->store($cacheKey, $items, 300);

        return $items;
    }

    protected function truncate($string, $limit, $break = ".", $pad = "...")
    {
        // return with no change if string is shorter than $limit
        if (strlen($string) <= $limit) {
            return $string;
        }

        // is $break present between $limit and the end of the string?
        if (false !== ($breakpoint = strpos($string, $break, $limit))) {
            if($breakpoint < strlen($string) - 1) {
                $string = substr($string, 0, $breakpoint) . $pad;
            }
        }
        return $string;
    }
}