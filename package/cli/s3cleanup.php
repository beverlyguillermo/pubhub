#!/usr/bin/php
<?php

date_default_timezone_set("America/New_York");

require __DIR__ . "/../vendor/autoload.php";

$s3 = new AmazonS3(array(
	"key" => "AKIAIWYLZYECRJNIRDIA",
	"secret" => "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"
));

$cleaner = new cli\classes\S3Cleaner($s3, "hubmanagerdb", array("prefix" => "hub"));
echo "\nBeginning s3Cleanup.php script.\n";
$cleaner->getFiles()->deleteFiles();
echo "s3Cleanup.php complete.\n";