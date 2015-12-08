<?php
require_once  __DIR__."/lib/include.php";
$logger = new Logger(__DIR__."/log");
$config = new Config(__DIR__."/config/config");
$tmpDirPath = __DIR__."/tmp";
$helper = new Helper($config, $logger);
$helper->backupVms($tmpDirPath);