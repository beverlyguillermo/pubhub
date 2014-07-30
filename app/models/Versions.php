<?php

namespace app\models;
use \app\workers\Database;
use \PDO;

class Versions extends Model
{
	protected $tableName = "versions";

	public $data = array();

	/**
	 * Returns an associative array of one page's
	 * active and schedule versions. This function
	 * handles calls to both functions required to
	 * perform the queries.
	 *
	 * @param $id integer representing the ID of the
	 * page whose data to retrieve
	 */
	public function findVersions($id)
	{
		// how to combine these SQL queries?
		$this->findActiveVersion($id);
		$this->findScheduledVersions($id);
		return $this;
	}

	/**
	 * Returns an associative array of one page's
	 * active version.
	 *
	 * @param $id integer representing the ID of the
	 * page whose data to retrieve
	 */
	public function findActiveVersion($id)
	{
		$sql = "SELECT * FROM {$this->tableName} WHERE page_id = {$id} AND published < NOW() AND deleted = 0 ORDER BY published DESC LIMIT 1";

		$stmt = $this->pdo->prepare($sql);
		$stmt->execute();

		$version = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$version = array_shift($version);

		// We have NO IDEA why this needs to be type casted as an array, but when we don't
		// sometimes we get "Invalid argument supplied for foreach()" errors here
		foreach ((array) $version as $key => $value) {
			if (@unserialize($value)) {
				$version[$key] = @unserialize($value);
			}
		}

		$this->data["active"] = $version;
		return $version;
	}

	/**
	 * Returns an associative array of one page's
	 * scheduled versions.
	 *
	 * @param $id integer representing the ID of the
	 * page whose data to retrieve
	 */
	private function findScheduledVersions($id)
	{
		$sql = "SELECT * FROM Versions WHERE page_id = {$id} AND published > NOW() AND deleted = 0 ORDER BY published DESC";

		$stmt = $this->pdo->prepare($sql);
		$stmt->execute();

		$versions = $stmt->fetchAll(PDO::FETCH_ASSOC);

		foreach ($versions as $version => $details) {
			foreach ($versions[$version] as $key => $value) {
				if (@unserialize($value)) {
					$versions[$version][$key] = @unserialize($value);
				}
			}
		}

		$this->data["scheduled"] = $versions;
	}
}