<?php
define("DATA_FOLDER", __DIR__."/backup");
define("FTP_ADDRESS", "192.168.0.1");
define("FTP_PATH", "/home-studio/vms-backup/");
define("FTP_USER", "admin");
define("FTP_PASSWORD", "admin");

$starttime = time();
mylog("Starting backing up\n");

$foldername = create_backup_folder(DATA_FOLDER);
export_vms($foldername);
upload_to_ftp($foldername);
delete_folder($foldername);

$time = time() - $starttime;
mylog("Backup complete in ".$time."s.");


/**
 * Удаление файла или папки (опасное)
 * @param String $foldername Путь к файлу
 */
function delete_folder($foldername){
    mylog("Removing ".$foldername);
    myexec("rm -rf ".$foldername);
    mylog("Done.");
}

/**
 * Загрузка папки на фтп сервер
 * @param String $foldername Путь к локальной папке
 */
function upload_to_ftp($foldername)
{
    $starttime = time();
    mylog("Uploading to ftp ".FTP_ADDRESS.FTP_PATH);
    $cmd = "ncftpput -R -u ".FTP_USER." -p ".FTP_PASSWORD." ".FTP_ADDRESS." ".FTP_PATH." ".$foldername;
    myexec($cmd);
    $time = time() - $starttime;
    mylog("Upload done in ".$time."s. ");
}

/**
 * Экспорт виртуальных машин
 * @param String $foldername Путь к папке куда экспортировать
 */
function export_vms($foldername)
{
    $starttime = time();
    $vms = get_vms();
    foreach($vms as $vm)
        export_vm($vm,$foldername);
    $time = time() - $starttime;
    mylog("Export done in ".$time."s.");
}

/**
 * Экспорт одной виртуальной машины. При необходимости машина выключается, затем ее работа будет возобновлена.
 * @param String $name Имя виртуальной машины
 * @param String $path Путь к папке в которую ее нужно экспортировать
 */
function export_vm($name,$path)
{
    mylog("Exporting ".$name." to ".$path);
    $runningvms = get_running_vms();
    $isRunning = in_array($name,$runningvms);
    if($isRunning){
        mylog("VM is running. Powering off.");
        myexec("VBoxManage controlvm ".$name." poweroff");
    }
    myexec("VBoxManage export ".$name." -o ".$path."/".$name.".ova");
    if($isRunning){
        mylog("Starting vm back again.");
        myexec("VBoxManage startvm ".$name." --type headless");
    }
}

/**
 * Создание папки для бекапов. Название содержит текущую дату.
 * 
 * @return String Путь к созданной папке.
 */
function create_backup_folder(){
   $name = date("Y-m-d_H:i");
   $path = DATA_FOLDER."/".$name;
   myexec("mkdir ".$path);
   return $path."/";
}