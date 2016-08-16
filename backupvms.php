<?php
require_once  __DIR__."/lib/include.php";
$logger = new Logger(__DIR__."/log");
$config = new Config(__DIR__."/config/config");
$helper = new Helper($config, $logger);
$params = new ConsoleParamManager($argv);

$vm  = $params->getParam("-vm");

$helper->debug = $params->hasFlag("--debug");

if($vm)
    $helper->backupVm($vm);
else
    $helper->backupVms();