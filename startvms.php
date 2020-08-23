<?php
#starts vms listed in config
require_once  __DIR__."/lib/include.php";
$logger = new Logger(__DIR__."/log");
$config = new Config(__DIR__."/config/config");
$helper = new Helper($config,$logger);
$helper->startVms();