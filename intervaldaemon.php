<?php
//Демон для запуска команд
require_once  __DIR__."/lib/include.php";

//Не запускаемся дважды
exec("ps -ax",$output);
$output = join("\n",$output);
$needle  = "0 php ".__DIR__."/intervaldaemon.php";
$numberOfProcesses = substr_count($output, $needle);


$logger = new Logger(__DIR__."/log",  true);
if($numberOfProcesses >= 2){
    $msg = "Daemon is already running";
    
    $logger->log($output);
    $logger->log($msg);
    die();
}
    




$config = new Config(__DIR__."/config/config");
$daemon = new CommandDaemon($config, $logger);
$daemon->start();
