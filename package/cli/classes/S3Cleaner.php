<?php

namespace cli\classes;

class S3Cleaner
{
	/**
	 * Amazon s3 object
	 * @var object
	 */
	protected $s3;

	/**
	 * Name of Amazon s3 bucket to target
	 * @var string
	 */
	protected $bucket;

	/**
	 * An associative array of parameters used when retrieving bucket files. See s3 docs:
	 * http://docs.aws.amazon.com/AWSSDKforPHP/latest/#m=AmazonS3/get_object_list
	 * @var array
	 */
	protected $options = array();

	/**
	 * All files in a bucket
	 * @var array
	 */
	public $files = array();

	/**
	 * Files to delete from bucket
	 * @var array
	 */
	public $filesToDelete = array();

	/**
	 * Files to keep in bucket
	 * @var array
	 */
	public $filesToKeep = array();

	/**
	 * Unix timestamp use to base all relative
	 * dates on (used for testing purposes)
	 * @var int (unix timestamp)
	 */
	protected $startTime;


	/**
	 * Constructor
	 * 
	 * @param object $s3 Amazon s3 object
	 * @param string $bucket Amazon s3 bucket to clean
	 * @param array $options An associative array of parameters used when
	 *        retrieving bucket files. See s3 docs:
	 *        http://docs.aws.amazon.com/AWSSDKforPHP/latest/#m=AmazonS3/get_object_list
	 */
	public function __construct($s3, $bucket, $options = array())
	{
		$this->s3 = $s3;

		if (!$this->s3->if_bucket_exists($bucket)) {
			throw new Exception("The Amazon s3 bucket specified does not exist");
		}

		$this->bucket = $bucket;
		$this->options = $options;
		$this->startTime = time();
	}


	/**
	 * Retrieves filenames from the specified Amazon s3 bucket and
	 * determines which are to be deleted.
	 * 
	 * @return object
	 */
	public function getFiles()
	{
		$this->files = $this->s3->get_object_list($this->bucket, $this->options);
		$this->setFilesToDelete();
		return $this;
	}


	/**
	 * Runs all files through findFilesToDelete()
	 * 
	 * @return object
	 */
	public function setFilesToDelete()
	{
		$this->filesToDelete = array_filter($this->files, array($this, "findFilesToDelete"));
		$this->filesToKeep = array_diff($this->files, $this->filesToDelete);
		return $this;
	}


	/**
	 * Deletes files that have been deemed unwanted.
	 * 
	 * @return null
	 */
	public function deleteFiles()
	{
		foreach($this->filesToDelete as $filename) {
			$this->s3->delete_object($this->bucket, $filename);
			echo "Deleted {$filename} from s3.\n";
		}
	}


	/**
	 * Analyzes a filename and determines if it needs to be
	 * deleted from the bucket.
	 * 
	 * @param string $filename Filename to be checked
	 * @return boolean (true to delete the file; false to keep the file)
	 */
	public function findFilesToDelete($filename)
	{
		// Files must match a certain pattern (ex: hubfactory-production-1234.sql.gzip)
		$match = preg_match("/^(hubfactory|hubmanager)_(\w+)_(\d{4}-\d{2}-\d{2})(\.\w+)*$/", $filename, $matches);
		
		// If the file doesn't match the pattern, delete it
		if (!$match) {
			return true;
		}

		$timestamp = strtotime($matches[3]);

		// delete anything older than a year
		if ($timestamp < strtotime("-1 year", $this->startTime)) {
			return true;
		}

		// keep every backup that was made on the first of every month no matter what
		if (date("j", $timestamp) == 1) {
			return false;
		}

		// keep every saturday backup that is not older than a month
		if (date("l", $timestamp) == "Saturday" && $timestamp > strtotime("-1 month", $this->startTime)) {
			return false;
		}

		// keep every daily backup that is not older than a week
		if ($timestamp > strtotime("-1 week", $this->startTime)) {
			return false;
		}

		// if nothing caught this file, delete it
		return true;
	}

	/**
	 * Sets the date by which all relative dates are calculated.
	 * Used only for testing purposes (if you want to feed an array
	 * of dates to $this->files that start in the future).
	 * 
	 * @param int $timestamp UNIX timestamp
	 */
	public function setStartDay($timestamp)
	{
		$this->startTime = $timestamp;
		return $this;
	}

}