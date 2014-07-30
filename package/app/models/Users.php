<?php

namespace app\models;

class Users extends Model
{
	protected $tableName = "users";

	protected $fields = array(
		"username",
		"hashed_password"
	);

	public function findByUsername($username)
	{
		$users = $this->findByField(array("username" => $username));
		return array_shift($users);
	}

	public function findByID($id)
	{
		$user = $this->findByField(array("id" => $id));
		return array_shift($user);
	}
}