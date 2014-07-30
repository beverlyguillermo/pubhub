<?php

/**
 * Config logs for the application based on environment
 * 
 */

use app\workers\Mailer;
use app\workers\Logger;
use Monolog\Logger as Monolog;

if (!defined("ENVIRONMENT")) {
	throw new \Exception("The environment has not been configured, so logging is disabled");
	return;
}

# Default logger for logging all messages
$log = Logger::createInstance("default", new Monolog("storefront"));
$env = strtolower(ENVIRONMENT);

if ($env === "development") {
	$logDir = "/var/www/html/hub/logs/";
}
if ($env === "staging" || $env === "production") {
	$logDir = "/var/www/html/hub/current/logs/";
}

if (!file_exists($logDir)) {
	throw new \Exception("Logs directory doesn't exist or isn't in the expected location.");
}

if (!is_writeable($logDir)) {
	throw new \Exception("Logs directory isn't writeable.");
} else {
	$logDir .= "";
	$mainFile = "application.log";

	if (!file_exists($logDir)) {
		try {
			mkdir($logDir, 0755);
		} catch (Exception $e) {
			throw new Exception("Could not find or create directory at " . $logDir . " (" . $e->getMessage() . ")");
		}
	}

	$logFile = $logDir . $mainFile;
	if (!fopen($logFile, "a")) {
		throw new Exception("Could not open or create file at " . $logFile);
	}
}

# Set main handler to log messages to the application.log file
$log->setFileHandler($logFile);

# Set email handler to send anything ERROR level or above via email
$mailer = new Mailer();
$messageTemplate = $mailer->newMessage()
	->setMessageTo(array("jrhodes@jhu.edu", "jwachter@jhu.edu"))
	->setMessageFrom(array("system@hub.jhu.com" => "Hub Monitor"))
	// ->setMessageBody("Do we need a body to make this work?")
	->setMessageSubject("[" . strtoupper(ENVIRONMENT) . "] Hub Error Alert")
	->getMessageObject();

$mailer = $mailer->getMailerObject();
$log->setEmailHandler($mailer, $messageTemplate, Monolog::ERROR, true);

