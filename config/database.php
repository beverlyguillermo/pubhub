<?php

use app\workers\Database;

Database::connections("development", array(
	"type" => "mysql",
	"database" => "hubmanager",
	"username" => "hubmanager",
	"password" => "password",
	"host" => "localhost"
));
