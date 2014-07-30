<?php

namespace app\models;
use \app\workers\Cache;

class Tweets extends Model
{
	protected $tableName = "tweets";

	protected $twitterhook;
	protected $options = array();
	protected $tweets = array();
	protected $cache;
	public $data = array();

	// http://local.hub.jhu.edu/twitter/api?screenName=HubJHU&typeTimeline=false&f%E2%80%A6showRetweets=false&typeList=true&typeFavs=true&listName=hub-communications

	public function __construct($options)
	{
		parent::__construct($options);
		$this->options = $options;

		$consumer = array(
			"key" =>"jc6JQfJz9vjG7ou7AL5A",
			"secret" => "dm3NA5wEkit0hHob3tYnPKMz1eNV7P5nTVbDRvPo"
		);

		$token = array(
			"token" =>"743066030-llN31UgkqvyB4xbwO6rrqHAsdFuXHO91VR3IzM25",
			"secret" => "RfPfX10VHFjFfyrDFoqu0X1LmYbA2j2UOBP74Y80"
		);

		$this->twitterhook = new \TwitterHook\Client($consumer, $token);

		$this->cache = new Cache();
	}

	public function getTweets()
	{
		$defaults = array(
			// API Calls
			"typeTimeline" => true,
			"typeFavs" => false,
			"typeList" => false,

			// Parameters
			"screenName" => "HubJHU",
			"count" => 20,
			"listName" => false,
			"showRetweets" => true, // NOTE: even if you don't show them, retweets factor into the 'count'

			// Twitter API call specific count
			"timelineCount" => false,
			"favsCount" => false,
			"listCount" => false,
		);

		$this->options = $this->options + $defaults;

		// Create key for caching purposes
		ksort($this->options);
		$key = "homepage-tweets?" . http_build_query($this->options);

		// Check if the tweets have been stored previously
		if ($tweets = $this->cache->fetch($key)) {
			$this->data["tweets"] = $tweets;
			return;
		}



		// If the tweets are not cached...

		extract($this->options);


		// Gather tweets
		
		if ($typeTimeline && $typeTimeline != "false") {
			$this->userTimeline();
		}

		if ($typeFavs && $typeFavs != "false") {
			$this->userFavs();
		}

		if ($typeList && $typeList != "false") {
			$this->userList();
		}


		// Throw out tweets that are older than 10 days
		
		$this->tweets = array_map(function($a) {
			$tweetTime = strtotime($a->created_at);
			$tenDaysAgo = strtotime("-10 days");

			if ($tweetTime < $tenDaysAgo) {
				return;
			} else {
				return $a;
			}


		}, $this->tweets);


		// Get rid of empty values potentially caused by the array_map
		
		$this->tweets = array_filter($this->tweets);


		// Sort tweets by date descending
		
		usort( $this->tweets, function ($a, $b) {
          $a_val = strtotime($a->created_at);
          $b_val = strtotime($b->created_at);
          if ( $a_val == $b_val ) {
            return 0;
          }
          return ($a_val > $b_val) ? -1 : 1;
        });


		// Assign the tweets to the model data
		$this->data["tweets"] = $this->tweets;

		// Cache the tweets for two minutes
		$this->cache->store($key, $this->tweets, 120);
	}

	protected function userTimeline()
	{
		extract($this->options);

		$params = array(
			"screen_name" => $screenName,
			"count" => $timelineCount ? $timelineCount : $count,
			"include_rts" => $showRetweets,
			"exclude_replies" => true,
			"include_entities" => true
		);

		$response = $this->twitterhook->get("statuses/user_timeline", $params);

		// add to tweets array
		foreach ($response as &$tweet) {
			$tweet->text = $this->cleanText($tweet->text);
			$this->tweets[] = $tweet;
		}
	}

	protected function userFavs()
	{
		// print_r($this->options); die();
		extract($this->options);

		$params = array(
			"screen_name" => $screenName,
			"count" => $favsCount ? $favsCount : $count,
			"include_entities" => true
		);

		$response = $this->twitterhook->get("favorites/list", $params);

		// add to tweets array
		foreach ($response as &$tweet) {

			$tweet->text = $this->cleanText($tweet->text);
			$this->tweets[] = $tweet;
		}
	}

	protected function userList()
	{
		extract($this->options);

		$params = array(
			"owner_screen_name" => $screenName,
			"slug" => $listName,
			"per_page" => $listCount ? $listCount : $count,
			"include_rts" => $showRetweets,
			"include_entities" => true
		);

		$response = $this->twitterhook->get("lists/statuses", $params);

		// add to tweets array
		foreach ($response as &$tweet) {
			$tweet->text = $this->cleanText($tweet->text);
			$this->tweets[] = $tweet;
		}
	}

	protected function cleanText($text)
	{
		$text = $this->link($text);
		$text = $this->hashTag($text);
		$text = $this->mention($text);
		return $text;
	}

	protected function link($text)
	{
		return preg_replace_callback("/[a-z]+:\/\/([a-z0-9-_]+\.[a-z0-9-_:~\+#%&\?\/.=]+[^:\.,\)\s*$])/i", function($matches) {
			$displayURL = strlen($matches[0]) > 36 ? substr($matches[0], 0, 35) . "&hellip;" : $matches[0];
			return "<a target='_newtab' href='$matches[0]'>$displayURL</a>";
		}, $text);
	}

	protected function mention($text)
	{
		return preg_replace("/(^|[^\w]+)\@([a-zA-Z0-9_]{1,15}(\/[a-zA-Z0-9-_]+)*)/", "$1<a target='_newtab' href='http://twitter.com/$2'>@$2</a>", $text);
	}

	protected function hashTag($text)
	{
		return preg_replace("/(^|[^&\w'\"]+)\#([a-zA-Z0-9_^\"^<]+)/", "$1<a target='_newtab' href='http://search.twitter.com/search?q=%23$2'>#$2</a>", $text);
	}
}