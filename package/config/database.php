<?php

use app\workers\Database;

Database::connections("development", array(
	"type" => "mysql",
	"database" => "hubmanager",
	"username" => "hubmanager",
	"password" => "password",
	"host" => "localhost"
));

// Other connections here, but how do we tell which "environment" we're in?

Database::connections("staging", array(
	"type" => "mysql",
	"database" => "hubmanagerstage",
	"username" => "hubmanagerstage",
	"password" => "ych4bj6ct686qgbf",
	"host" => "esgwebmysql.win.ad.jhu.edu"
));

Database::connections("production", array(
	"type" => "mysql",
	"database" => "hubmanager",
	"username" => "hubmanager",
	"password" => "nq4u4r3my37xv5kd",
	"host" => "esgwebmysql.win.ad.jhu.edu"
));