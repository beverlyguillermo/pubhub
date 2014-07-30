<?php

namespace app\models;
use \app\workers\Messages;

class Hubstagram extends Model
{
	protected $tableName = "hubstagram";

	/**
	 * Instahook
	 * https://github.com/johnshopkins/Instahook
	 * 
	 * @var object
	 */
	protected $instahook;

	/**
	 * Tag streams to get
	 * @var array
	 */
	protected $tagStreams = array();

	/**
	 * Location streams to get
	 * @var array
	 */
	protected $locationStreams = array();

	/**
	 * Tags used to filter photos from
	 * specific streams
	 * @var array
	 */
	protected $tagFilters = array();

	/**
	 * Usernames whose photos are banned
	 * from the stream.
	 * @var array
	 */
	public $bannedUsers = array();

	/**
	 * Photos that are banned from the stream.
	 * @var array
	 */
	public $bannedPhotos = array();

	/**
	 * All media retrieved from streams
	 * in an unsorted array.
	 * @var array
	 */
	protected $returnedMedia = array();

	/**
	 * All tags on all media
	 * returned from streams
	 * @var array
	 */
	public $returnedTags = array();

	/**
	 * All locations on all media
	 * returned from streams
	 * @var array
	 */
	public $returnedLocations = array();

	/**
	 * Media recently liked by the
	 * authenticated user (JohnsHopkins)
	 * @var array
	 */
	protected $recentLikes = array();

	/**
	 * Time to cache shtuff
	 * @var integer
	 */
	protected $ttl = 2100;

	/**
	 * [$updateCache description]
	 * @var boolean
	 */
	protected $updateCache = false;

	/**
	 * Users or photos submitted to the
	 * manager ban form
	 * @var array
	 */
	protected $banValues = array();

	public function __construct($options)
    {
    	parent::__construct($options);

    	$this->setTableName("hubpix_banned");
    	$this->updateCache = !empty($options["updateCache"]) ? $options["updateCache"] : null;
    	$this->getBannedUsers();
    	$this->getBannedPhotos();
	}

    public function setTemplate()
    {
    	$this->data["template"] = "pages/hub/pix";
    }

	/**
	 * Add a tag media stream to the array of streams
	 * that will be retrieved
	 * 
	 * @param  string 	$tag   		Instagram tag (with or without #)
	 * @param  boolean 	$filtering 	If true, the stream will go through filtering
	 * @return self
	 */
	public function addTagStream($tag, $filtering = false)
	{
		$this->tagStreams[] = array(
			"name" => ltrim($tag, "#"),
			"filtering" => $filtering
		);
		return $this;
	}

	/**
	 * Add multiple tag streams. All streams passed to this
	 * function will use the single filtering value.
	 * 
	 * @param array  	$tags      Array of Instagram tags (with or without #)
	 * @param boolean 	$filtering If true, the stream will go through filtering
	 * @return self
	 */
	public function addTagStreams($tags, $filtering = false)
	{
		foreach ($tags as $tag) {
			$this->tagStreams[] = array(
				"name" => ltrim($tag, "#"),
				"filtering" => $filtering
			);
		}
		return $this;
	}

	/**
	 * Add a location media stream to the array of streams
	 * that will be retrieved
	 * 
	 * @param  string 	$location 	Foursquare location ID
	 * @param  boolean 	$filtering 	If true, the stream will go through filtering
	 * @return self
	 */
	public function addLocationStream($location, $filtering = false)
	{
		$this->locationStreams[] = array(
			"location" => $location,
			"filtering" => $filtering
		);
		return $this;
	}

	/**
	 * Add multiple location streams. All streams passed to this
	 * function will use the single filtering value.
	 * 
	 * @param array  	$locations 	Array of Foursquare location IDs
	 * @param boolean 	$filtering 	If true, the stream will go through filtering
	 * @return self
	 */
	public function addLocationStreams($locations, $filtering = false)
	{
		foreach ($locations as $location) {
			$this->locationStreams[] = array(
				"location" => $location,
				"filtering" => $filtering
			);
		}
		return $this;
	}

	public function getBannedUsers()
	{
		$fields = array("type" => "user");
        $users = parent::findByField($fields);

        $recently = $this->getRecently();
        $this->bannedUsers = array();
        foreach ($users as $user) {
        	$this->bannedUsers[] = $user["value"];

        	if (strtotime($user["timestamp"]) > $recently) {
        		$this->data["recentlyBannedUsers"][] = $user["value"];
        	}
        }
	}

	public function getBannedPhotos()
	{
		$fields = array("type" => "photo");
        $photos = parent::findByField($fields);

        $recently = $this->getRecently();
        $this->bannedPhotos = array();
        foreach ($photos as $photo) {
        	$this->bannedPhotos[] = $photo["value"];

        	if (strtotime($photo["timestamp"]) > $recently) {
        		$this->data["recentlyBannedPhotos"][] = $photo["value"];
        	}
        }
	}

	public function getRecently()
	{
		return time() - $this->ttl;
	}


	/**
	 * Retrieve data from streams
	 * 
	 * @return self
	 */
	public function process()
	{
		$key = "hubpix-data";

		if (!$this->updateCache && $cached = apc_fetch($key)) {
			$this->returnedMedia = $cached["returnedMedia"];
			$this->returnedTags = $cached["returnedTags"];
			$this->returnedLocations = $cached["returnedLocations"];
			$this->recentLikes = $cached["recentLikes"];
			return $this;
		}

		foreach ($this->tagStreams as $tag) {
			$media = $this->getTagStreamMedia($tag["name"], $tag["filtering"]);
			$this->processMedia($media);
		}

		foreach ($this->locationStreams as $location) {
			$media = $this->getMediaByLocationID($location["location"], $location["filtering"]);
			$this->processMedia($media);
		}

		// Organize media, tags, locations arrays
		$this->returnedMedia = $this->sortArrayOfArrayByKey($this->returnedMedia, "created_time", "desc");
		$this->returnedTags = $this->sortArrayOfArrayByKey($this->returnedTags, "count", "desc");
		$this->returnedLocations = $this->sortArrayOfArrayByKey($this->returnedLocations, "count", "desc");


		$this->processRecentLikes();


		// store for four hours
		apc_store($key, array(
			"returnedMedia" => $this->returnedMedia,
			"returnedTags" => $this->returnedTags,
			"returnedLocations" => $this->returnedLocations,
			"recentLikes" => $this->recentLikes
		), $this->ttl);

		return $this;
	}

	/**
	 * Puts data into appropiate locations in $data
	 * @param  boolean $shuffle
	 * @param  integer Number of days worth of media to return.
	 * @return null
	 */
	public function sortData($shuffle = true, $days = "all")
	{
		$this->data["media"] = $this->getReturnedMedia($shuffle, $days);
        $this->data["filters"] = array(
            "locations" => $this->returnedLocations,
            "tags" => $this->returnedTags
        );
	}

	/**
	 * Process each photo by adding it to the returnedMedia
	 * array and extracting tag, location information.
	 * 
	 * @param  array $media Array of media
	 * @return null
	 */
	protected function processMedia($media)
	{
		foreach ($media as &$photo) {
			$photo->urlId = $this->getUrlId($photo);

			if ($this->isUserBanned($photo) || $this->isPhotoBanned($photo)) {
				continue;
			}

			if (empty($this->returnedMedia[$photo->id])) {

				$this->returnedMedia[$photo->id] = $photo;

				foreach($photo->tags as $tag) {
					if (strlen($tag) > 2) {
						$this->addTag($tag);
					}
				}

				if (!empty($photo->location->name)) {
					$this->addLocation($photo->location);
				}
			}
		}
	}

	protected function getUrlId($photo)
	{
		preg_match("/https?:\/\/[a-zA-Z0-9.]+\/p\/(\S+)\//", $photo->link, $matches);
		return !empty($matches[1]) ? $matches[1] : null;
	}

	public function processRecentLikes()
	{
		$this->recentLikes = $this->getRecentLikesFromAPI(5);

		// store in a database
		$dbValues = array();
		foreach ($this->recentLikes as $photo) {
			$dbValues[] = array(
				"photo_id" => $photo->id,
				"timestamp" => time(),
				"created_time" => $photo->created_time
			);
		}

		if (!empty($dbValues)) {
			$this->tableName = "hubpix_likes";

			foreach ($dbValues as $value) {
				$this->create($value, true);
			}
		}
	}

	/**
	 * Check to see if a user has been banned
	 * from the photo stream.
	 * @param  object  $photo Photo object
	 * @return boolean TRUE if banned; FALSE if not banned.
	 */
	protected function isUserBanned($photo)
	{
		$username = $photo->user->username;

		if (in_array($username, $this->bannedUsers)) {
			return true;
		}
		return false;
	}

	/**
	 * Check to see if a photo has been banned
	 * from the photo stream.
	 * @param  object  $photo Photo object
	 * @return boolean TRUE if banned; FALSE if not banned.
	 */
	protected function isPhotoBanned($photo)
	{
		if (in_array($photo->urlId, $this->bannedPhotos)) {
			return true;
		}
		return false;
	}

	/**
	 * Add the given tag to the list of returned tags
	 * @param string $tag Tag name
	 */
	protected function addTag($tag)
	{
		if (isset($this->returnedTags[$tag])) {
			$this->returnedTags[$tag]["count"]++;
		} else {
			$this->returnedTags[$tag] = array(
				"name" => $tag,
				"count" => 1
			);
		}
	}

	/**
	 * Add a location to the list of returned locations
	 * if there is an ID set. This ensures we only get
	 * registered locations vs. lat/long.
	 * 
	 * @param object $location Location object
	 */
	protected function addLocation($location)
	{
		$name = !empty($location->name) ? $location->name: null;
		$id = !empty($location->id) ? $location->id : null;

		if (isset($this->returnedLocations[$id])) {
			$this->returnedLocations[$id]["count"]++;
		} else {
			$this->returnedLocations[$id] = array(
				"name" => $name,
				"id" => $id,
				"count" => 1
			);
		}
	}

	/**
	 * Add tags to a list of filters to sort the returned photos by.
	 * 
	 * @param string $tag Instagram tag (with or without #)
	 * @return self
	 */
	public function addFilter($tag)
	{
		$this->tagFilters[] = ltrim($tag, "#");
		return $this;
	}

	public function addFilters($tags)
	{
		foreach ($tags as $tag) {
			$this->addFilter($tag);
		}
		return $this;
	}

	/**
	 * Provides a shortcut to Instahook's getMediaByTag() method
	 * and the subsequent need to drill into the object that is
	 * returned.
	 * 
	 * @param  string   $tag   		Tag
	 * @param  boolean 	$filtering 	If true, the stream will go through filtering
	 * @param  integer  $count 		Number of photos to return
	 * @return array
	 */
	protected function getTagStreamMedia($tag, $filtering = false, $count = 15)
	{
		$media = $this->instahook->getMediaByTag($tag, array("count" => $count));
		$media = $media->data;
		
		if ($filtering) {
			$media = $this->filterMedia($media);
		}
		return $media;
	}

	/**
	 * Provides a shortcut to Instahook's getMediaByLocationID() method
	 * and the subsequent need to drill into the object that is
	 * returned.
	 * 
	 * @param  string  	$locationId   	Location ID
	 * @param  boolean 	$filtering 		If true, the stream will go through filtering
	 * @param  integer 	$count 		 	Number of photos to return
	 * @return array
	 */
	protected function getMediaByLocationID($locationId, $filtering = false, $count = 15)
	{
		$media = $this->instahook->getMediaByLocationID($locationId, array("count" => $count));
		$media = $media->data;
		
		if ($filtering) {
			$media = $this->filterMedia($media);
		}

		return $media;
	}


	/**
	 * Gets the recent liked media by the authenticated user
	 * @return array
	 */
	protected function getRecentLikesFromAPI($count = 5)
	{
		$params = array("count" => $count);
		$media = $this->instahook->getUserRecentLikes();
		return $media->data;
	}

	/**
	 * Gets the most recent liked photo from the database
	 * that was liked within the past 24 hours.
	 * @return array
	 */
	public function getRecentLikedPhoto()
	{
		$sql = "SELECT * FROM hubpix_likes WHERE timestamp < ? AND timestamp > ? AND deleted = 0 ORDER BY timestamp DESC, created_time DESC LIMIT 1";
		$values = array(
			time(),
			strtotime("-1 day")
		);

		$result = $this->query($sql, $values);
		$result = array_shift($result);

		$photo = $this->instahook->getMediaById($result["photo_id"]);
		return $photo->data;
	}


	protected function filterMedia($media)
	{
		$return = array();
		foreach ($media as $photo) {
			if (array_intersect($this->tagFilters, $photo->tags)) {
				$return[] = $photo;
			}
		}
		return $return;
	}

	/**
	 * Get ALL media returned.
	 * 
	 * @param  boolean $shuffle
	 * @param  integer Number of days worth of media to return. Note: this
	 *                 number will only affect the MEDIA returned, not the
	 *                 tags and locations. I'm subscribing to YAGNI right now.
	 *                 If we need it, we'll add it later. - jw
	 * @return array
	 */
	public function getReturnedMedia($shuffle = true, $days = "all")
	{
		if ($shuffle) {
			shuffle($this->returnedMedia);
		}

		if ($days == "all") {
			return $this->returnedMedia;
		}

		// Limit photos to {$days} days worth
		$photos =  array_map(function($photo) use ($days) {
            if (strtotime("-{$days} days") <= $photo->created_time) {
                return $photo;
            }
        }, $this->returnedMedia);

        // get rid of empty values
        return array_filter($photos);
	}

	/**
	 * Sort an array that contains associative arrays by
	 * a common key
	 * 
	 * @param  array 	$array      The array to sort
	 * @param  string 	$sortByKey 	Common key to sort by
	 * @param  string 	$order     	ASC or DESC
	 * @return array 	The sorted array
	 */
	public function sortArrayOfArrayByKey($array, $property, $order = "desc", $cmp = "numeric")
	{
		uasort($array, function ($a, $b) use ($property, $order, $cmp) {

			$dateElements = array("created_time");

			if (is_array($a)) {
				$a_val = $a[$property];
				$b_val = $b[$property];
			} else {
				$a_val = $a->$property;
				$b_val = $b->$property;
			}

			if ($cmp == "numeric") {
				if ($order == "asc") {
					return $a_val < $b_val;
				} elseif ($order == "desc") {
					return $b_val > $a_val;
				}

			} else {
				if ($order == "asc") {
					return strcmp($a_val, $b_val);
				} elseif ($order == "desc") {
					return strcmp($b_val, $a_val);
				}
			} 
		});

		return $array;
	}

	/**
	 * Process the Manager's ban form
	 * @return [type] [description]
	 */
	public function processBanForm(array $values)
	{
		$this->tableName = "hubpix_banned";

		$this->setProcessingVariables($values);

		if (!empty($this->banValues)) {
			foreach ($this->banValues as $ban) {
				$this->create($ban);
			}
			Messages::push("success", "The user(s) and/or photo(s) were successfully banned. Good job!");
		} else {
			Messages::push("alert", "The banning of a user or photo was not successful because you did not provide a valid photo or user ID. Please try again.");
		}

		$this->router->redirect("/manager/hubpix");
	}

	protected function setProcessingVariables($values)
	{
		$this->banValues = array();

		foreach($values["banned"] as $key => $value) {
			if (!empty($value["value"])) {
				$this->banValues[] = $value;
			}
		}
	}

}