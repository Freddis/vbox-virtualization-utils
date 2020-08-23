<?php
//Daemon for backing up virtual machines
require_once  __DIR__."/lib/include.php";

$logger = new Logger(__DIR__."/log",  true);
$config = new Config(__DIR__."/config/config");
$daemon = new BackupDaemon($config, $logger);
$daemon->start();

//The old way that used cron and interval daemon
//I decided to leave it be to remind me of the way

//starting the daemon
//$logger->log("Starting interval daemon");
//$result = exec("php ".__DIR__."/intervaldaemon.php > /dev/null &");
//$logger->log($result);