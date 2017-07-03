<?php
require_once  __DIR__."/lib/include.php";
$logger = new Logger(__DIR__."/log");
$config = new Config(__DIR__."/config/config");
$helper = new Helper($config, $logger);
$params = new ConsoleParamManager($argv);



$helper->debug = $params->hasFlag("--debug");

if($params->hasFlag("backup"))
{
    $vm  = $params->getParam("--vm");
    $all = $params->hasFlag("--all");
    if($vm)
        return $helper->backupVm($vm);
    if($all)
        return $helper->backupVms();
    
    echo "Please, specify parameters: \n";
    echo "'backup --vm name' making backup of particular virtual machine\n";
    echo "'backup --all' making backup of all virtual machines\n";   
    return;
}

if($params->hasFlag("storage"))
{
    $info = $params->hasFlag("list");
    if($info)
        return $helper->showBackupList();
    $info = $params->hasFlag("info");
    if($info)
        return $helper->showBackupInfo();
    $fix = $params->hasFlag("fix");
    if($fix)
        return $helper->removeEmptyBackups();
    $clear=  $params->getParam("clear");
    if($clear)
        return $helper->removeOldBackups($clear);
    
    echo "Please, specify parameters: \n";
    echo "'storage info' show information about available backups\n";
    echo "'storage clear 3' clears storage and leaves 3 latest backups\n";  
    echo "'storage fix' clears storage from empty backups \n";  
    return;
}


 echo "Please, specify the action: \n";
 echo "backup \n";
 echo "storage \n";
