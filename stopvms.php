<?php
include(__DIR__."/include/functions.php");
mylog("Stopping all vms.");
$vms = get_running_vms();
foreach ($vms as $vm)
    stop_vm($vm);
mylog("Everything done.");

/**
 * Остановка виртуальной машины
 * @param String $name Имя виртуальной машины
 */
function stop_vm($name){
    mylog("Stoping vm ".$name);
    $vms = get_vms();
    if(!in_array($name, $vms)){
        mylog("Can't find vm ".$name.". Skipping.");
        return;
    }
    $running_vms = get_running_vms();
    if(!in_array($name, $running_vms)){
        mylog("Vm ".$name." is already stoped. Skipping.");
        return;
    }
    myexec("VBoxManage controlvm ".$name." poweroff");
    mylog("Done.");
}