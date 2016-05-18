<?php
require_once  __DIR__."/lib/include.php";
$logger = new Logger(__DIR__."/log");
$config = new Config(__DIR__."/config/config");
$helper = new Helper($config,$logger);
$params = new ConsoleParamManager($argv);

$vm = $params->getParam("-name");
$all = !$params->hasFlag("--all");
$helper->showInfo($all,$vm);