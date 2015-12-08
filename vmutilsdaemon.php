<?php
//Демон для бэкапов виртуальных машин
require_once  __DIR__."/lib/include.php";

$logger = new Logger(__DIR__."/log",  true);
$config = new Config(__DIR__."/config/config");
$daemon = new BackupDaemon($config, $logger);
$daemon->start();