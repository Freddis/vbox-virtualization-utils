<?php
//Демон для бэкапов виртуальных машин
require_once  __DIR__."/lib/include.php";

$logger = new Logger(__DIR__."/log",  true);
$config = new Config(__DIR__."/config/config");
$daemon = new BackupDaemon($config, $logger);
$daemon->start();

//запускаем другого демона
$logger->log("Starting interval daemon");
$result = exec("php ".__DIR__."/intervaldaemon.php > /dev/null &");
$logger->log($result);