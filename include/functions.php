<?php
//Фикс дат
date_default_timezone_set("Etc/GMT+3");

/**
 * Логирование сообщения.
 * @param String $msg Сообщение, которое необходимо залогировать
 */
function mylog($msg){
    $time = date("Y-m-d H:i:s");
    echo $time." - ".$msg."\n";
}

/**
 * Выполнение команды unix. 
 * @param String $command Команда
 * @return String Результат выполнения команды
 */
function myexec($command){
    mylog("--exec-- ".$command);
    $result = shell_exec($command);
    mylog("--result--".$result);
    return $result;
}

/**
 * Получение списка зарегистрированных виртуальных машин
 * 
 * @return String[] Список имен виртуальных машин
 */
function get_vms()
{
    $output = myexec("VBoxManage list vms");
    $vms = get_vm_names($output);
    return $vms;
}

/**
 * Получение списка запущенных виртуальных машин
 * 
 * @return String[] Список имен виртуальных машин
 */
function get_running_vms()
{
    $output = myexec("VBoxManage list runningvms");
    $vms = get_vm_names($output);
    return $vms;
}


/**
 * Получает имена виртуальных машин из списка, который выводит команда VBoxManage
 * 
 * @param String $output Вывод команды VBoxManage list.
 * @return String[] Массив имен виртуальных машин
 */
function get_vm_names($output)
{
    $result = array();
    $strs = split("\n",$output);
    foreach($strs as $str)
    {
        if(!$str)
            continue;
        $splat = split("\"",$str);
        $name = $splat[1];
        array_push($result, $name);
    }
    return $result;
}