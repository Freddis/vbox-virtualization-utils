<?php
include(__DIR__."/include/functions.php");
mylog("Starting up vms.");

$vms_file = __DIR__."/config/vms_to_start";
if(!file_exists($vms_file))
{
    mylog("File not found ".$vms_file);
    mylog("You should create this file and put there list of vms to start delimited with comma.");
    mylog("Example:");
    mylog("mysql,redmine,gogs,frontend-server");
    exit;
}

$content = file_get_contents($vms_file);
mylog("Vms to start: ".$content);
$vms = split(",",$content);
foreach ($vms as $vm)
    start_vm($vm);

mylog("Everything done.");

/**
 * Запуск виртуальной машины
 * @param String $name Имя виртуальной машины
 */
function start_vm($name){
    mylog("Starting vm ".$name);
    $vms = get_vms();
    if(!in_array($name, $vms)){
        mylog("Can't find vm ".$name.". Skipping.");
        return;
    }
    $running_vms = get_running_vms();
    if(in_array($name, $running_vms)){
        mylog("Vm ".$name." is already started. Skipping.");
        return;
    }
    myexec("VBoxManage startvm ".$name." --type headless");
    mylog("Done.");
}