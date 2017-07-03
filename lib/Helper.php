<?php
/**
 * Объект, который заменяет функции.
 * Понадобился по причине необходимости логирования.
 * 
 * @author Sarychev Alexei <freddis336@gmail.com>
 */
class Helper
{
    /**
     * Режим дебага. Пропускает тяжелые операции.
     * @var bool
     */
    public $debug = false;
    
    /**
     * Производит больше вывода на экран, фактически все, что пишется в лог.
     * @var type 
     */
    public $verbouse;
    
    /**
     * Объект для логирования
     * @var Logger 
     */
    protected $logger;
    
    /**
     * Параметры
     * @var Config
     */
    protected $config; 
    
    /**
     * Конструктор
     * @param Logger $logger Объект для логирования
     */
    public function Helper(Config $config, Logger $logger){
	$this->logger = $logger;
	$this->config = $config;
    }
    
    /**
     * Запуск виртуальных машин, заданные в настройках
     */
    public function startvms(){
	$this->logger->console("Starting up vms.");
	
	$content = $this->config->getParam("vms_to_start");
	$this->logger->console("Vms to start: ".$content);
	$vms = explode(",",$content);
	foreach ($vms as $vm)
	    $this->startVm($vm);
	
	$this->logger->console("Everything done.");
    }
    
    /**
     * Остановка всех виртуальных машин
     */
    public function stopVms(){
	$this->logger->console("Stopping all vms.");
	$vms = $this->getRunningVms();
	foreach ($vms as $vm)
	    $this->stopVm($vm);
	$this->logger->console("Everything done.");
    }
    
    /**
    * Остановка виртуальной машины
    * @param String $name Имя виртуальной машины
    */
   public function stopVm($name){
       $this->logger->console("Stoping vm ".$name);
       $vms = $this->getVms();
       if(!in_array($name, $vms)){
	   $this->logger->console("Can't find vm ".$name.". Skipping.");
	   return;
       }
       $running_vms = $this->getRunningVms();
       if(!in_array($name, $running_vms)){
	   $this->logger->console("Vm ".$name." is already stoped. Skipping.");
	   return;
       }
       $this->exec("VBoxManage controlvm ".$name." poweroff");
       $this->logger->console("Done.");
   }
   
    /**
     * Создание резервной копии виртуальных машин и отправка ее на FTP сервер
     * 
     * @throws Exception
     */
    public function backupVms(){	
        $this->logger->console("Starting backup\n");
	$starttime = time();
          
        $vms = $this->getVms();
        foreach($vms as $vm)
        {
            $this->backupVm($vm);
        }

	$time = time() - $starttime;
	$this->logger->console("Backup complete in ".$time."s.");	
    }
    
    /**
     * Резервное копирование одной виртуальной машины
     * 
     * @param String $vm Имя виртуальной машины
     */
    public function backupVm($vm)
    {
       $this->logger->console("Starting backup for $vm\n");
       $foldername = $this->createBackupFolder();
       $date = date("Y-m-d_H:i:s");
       $filename = $vm."_".$date;
       $starttime = time();
       $path = $this->exportVm($vm,$foldername,$filename);
       $time = time() - $starttime;
    
       $this->logger->console("Export done in ".$time."s.");
       
       //Проверяем размер, чтобы не загружать бекапы по 100 раз
       $size = filesize($path);
       $lastBackupSize = $this->getSizeOfPreviousBackup($vm);
       if($size != $lastBackupSize)
       {
           $this->uploadToFTP($path);
       }
       else {
           $this->logger->console("Skipping, cause size of the backup isn't changed: $size");
       }
       
       //В дебаге не удаляем файлы
       if(!$this->debug)
        $this->deleteFolder($foldername);
    }
    
    /**
     * Выполнение команды unix. 
     * @param String $command Команда
     * @return String Результат выполнения команды
     */
    public function exec($command){
	//Некоторые команды не выполняются под рутом, поэтому вот так
	$configuser = $this->config->getParam("user",true);
	$user = $this->getCurrentUser();
	if($configuser != null)
	    if($user != $configuser)
		 $command = "su -c \"$command\" $configuser";
	
	$this->logger->log("--exec user:$user conf:$configuser-- ".$command);
	$result = shell_exec($command);
	$this->logger->log("--result--".$result);
	return $result;
    }
    
    /**
     * Выполнение команды unix с учетом режима дебага. В режиме дебага они не выполняются.
     * 
     * @param String $command Команда
     * @return String Результат выполнения команды или пустая строка
     */
    protected function debugExec($command){
	if(!$this->debug)
	    return $this->exec ($command);
	$this->logger->log("--exec-- ".$command);
	$this->logger->log("--result--"."--DEBUG--");
	return "";
    }
    /**
     * Получение списка зарегистрированных виртуальных машин
     * 
     * @return String[] Список имен виртуальных машин
     */
    public function getVms()
    {
	$output = $this->exec("VBoxManage list vms");
	$vms = $this->getVmsNames($output);
	return $vms;
    }

    /**
     * Получение списка запущенных виртуальных машин
     * 
     * @return String[] Список имен виртуальных машин
     */
    public function getRunningVms()
    {
	$output = $this->exec("VBoxManage list runningvms");
	$vms = $this->getVmsNames($output);
	return $vms;
    }

    /**
     * Отображает информацию о виртуальных машинах,
     * 
     * @param Bool $running Если True, то информация отобразится только для запущенных виртуальных машин
     * @param String $vm  Имя конкретной виртуальной машины для отображения
     */
    public function showInfo($running = true,$vm = null){
	$noTotal = false;
	
	//Если задана конкретная машина, то отображаем ее инфо
	if($vm){
	    $allVms = $this->getVms();
	    $vms = array_intersect($allVms, array($vm));
	    if(empty($vms))
		throw  new Exception ("Machine '$vm' is not found.");
	    $noTotal = true;
	}
	else {
	   
	    if($running)
		$vms = $this->getRunningVms();
	    else
		$vms = $this->getVms();
	}
	$totalMemory  = 0;
	foreach($vms as $vm){
	    $arr = array();
	    exec("vboxmanage showvminfo $vm",$arr);
	    
	    $name = $this->parseVMInfo("Name",$arr);
	    $memory = $this->parseVmInfo("Memory size",$arr);
	    $mac = $this->parseVmInfo("MAC",$arr);
	    $cpu = $this->parseVmInfo("CPU exec",$arr);
	    $state = $this->parseVmInfo("State",$arr);
	    $os = $this->parseVmInfo("OS type",$arr);
	    $vram = $this->parseVmInfo("VRAM",$arr);
	    
	    //Чистим строку мака от лишней инфы
	    $mac = explode(",",$mac)[0];
	    //Правим пробелы для операционки
	    $os = str_replace("           ", " ", $os);
	    
	    //Собираем кол-во оперативки для того, чтобы получить общую
	    $memline = explode(" ",$memory)[6];
	    $memNumber = intval($memline);
	    $totalMemory +=$memNumber;
	    
	    echo "$name \n";
	    echo "$os \n";
	    echo "$state\n";
	    echo "$memory\n";
	    echo "$cpu\n";
	    echo "$vram\n";
	    echo "$mac\n";
	    echo "\n";
	}
	if($noTotal)
	    return;
	echo "----------\n";
	echo count($vms)." vms \n";
	echo "Memory total: ".$totalMemory." MB \n";
	
    }
    
    /**
     * Парсит результат работы команды showvminfo с целью получения нужных строк
     * 
     * @param String $name Подстрока
     * @param String $arr[] Массив строк, полученный после exec("vboxmanage showvminfo vm")
     * @return String Искомая строка
     */
    protected function parseVmInfo($name,$arr){
	foreach($arr as $line)
	    if(strpos($line,$name) !== false)
		return $line;

	return "";
    }
    
    /**
     * Получает имена виртуальных машин из списка, который выводит команда VBoxManage
     * 
     * @param String $output Вывод команды VBoxManage list.
     * @return String[] Массив имен виртуальных машин
     */
    protected function getVmsNames($output)
    {
	$result = array();
	$strs = explode("\n",$output);
	foreach($strs as $str)
	{
	    if(!$str)
		continue;
	    $splat = explode("\"",$str);
	    $name = $splat[1];
	    array_push($result, $name);
	}
	return $result;
    }
    
    /**
    * Удаление файла или папки (опасное)
    * @param String $foldername Путь к файлу
    */
   public function deleteFolder($foldername){
       $this->logger->console("Removing ".$foldername);
       $this->exec("rm -r ".$foldername);
       $this->logger->console("Done.");
   }

   /**
    * Загрузка папки на фтп сервер
    * @param String $foldername Путь к локальной папке
    */
   public function uploadToFTP($foldername)
   {
       $starttime = time();
       $ftp_address = $this->config->getParam("ftp_address");
       $ftp_path = $this->config->getParam("ftp_path");
       $ftp_user = $this->config->getParam("ftp_user");
       $ftp_password = $this->config->getParam("ftp_password");
       
       $this->logger->console("Uploading to ftp '$ftp_address$ftp_path'");
       $cmd = "ncftpput -R -u $ftp_user -p $ftp_password $ftp_address $ftp_path ".$foldername;
       $this->logger->console($cmd);
       $this->exec($cmd);
       $time = time() - $starttime;
       $this->logger->console("Upload done in ".$time."s. ");
   }

   /**
    * Экспорт виртуальных машин
    * @param String $foldername Путь к папке куда экспортировать
    */
   public function exportVms($foldername)
   {
       $starttime = time();
       $vms = $this->getVms();
       foreach($vms as $vm)
       {
           $this->exportVm($vm,$foldername);
       }
       $time = time() - $starttime;
       $this->logger->console("Export done in ".$time."s.");
   }

   /**
    * Экспорт одной виртуальной машины. При необходимости машина выключается, затем ее работа будет возобновлена.
    * @param String $name Имя виртуальной машины
    * @param String $path Путь к папке в которую ее нужно экспортировать
    * @param String $filename Имя файла с которым сохранять
    * @return String Полный путь к файлу экспорта
    */
   public function exportVm($name,$path,$filename =null)
   {
       $this->logger->console("Exporting ".$name." to ".$path);
       $runningvms = $this->getRunningVms();
       $isRunning = in_array($name,$runningvms);
       if($isRunning){
	   $this->logger->console("VM is running. Powering off.");
	   $this->debugExec("VBoxManage controlvm ".$name." poweroff");
       }
       $this->logger->console("Exporting...");
       $filename = $filename ? $filename : $name;
       $fullpath = $path."/".$filename.".ova";
       
       if($this->debug)
       {
          $cmd = "echo ratatatatata > ".$fullpath;
          //print_r($cmd);
          exec($cmd);
       }
       else
       {
           $this->debugExec("VBoxManage export ".$name." -o ".$fullpath);
       }
       
       if($isRunning){
	   $this->logger->console("Starting vm back again.");
	   $this->debugExec("VBoxManage startvm ".$name." --type headless");
           $timing = $this->debug ? 2 : 60;
           $this->logger->console("Waiting {$timing}s: giving time for vm to start \n");
           sleep($timing);
       }
       return $fullpath;
   }

   /**
    * Создание папки для бекапов. Название содержит текущую дату.
    */
   public function createBackupFolder(){
      $path = $this->config->getParam("path_to_temp_folder");
      if(!is_writable($path))
	    throw new Exception ("File either not writable or not exists: $path");   
      $name = "tmp";//date("Y-m-d_H:i");
      $path = $path."/".$name;
      
      if(!file_exists($path))
        $this->exec("mkdir ".$path);
      return $path."/";
   }
   
    /**
    * Запуск виртуальной машины
    * 
    * @param String $name Имя виртуальной машины
    */
   public function startVm($name){
       $this->logger->console("Starting vm ".$name);
       $vms = $this->getVms();
       if(!in_array($name, $vms)){
	   $this->logger->console("Can't find vm ".$name.". Skipping.");
	   return;
       }
       $running_vms = $this->getRunningVms();
       if(in_array($name, $running_vms)){
	   $this->logger->console("Vm ".$name." is already started. Skipping.");
	   return;
       }
       $this->exec("VBoxManage startvm ".$name." --type headless");
       $this->logger->console("Done.");
       $timeout = $this->config->getParam("timeout_between_vms");
       $this->logger->console("Waiting {$timeout}s.");
       sleep($timeout);
   }
   
   /**
    * Возвращает пользователя от имени которого запущен скрипт.
    * Этот способ довольно быстрый, но требует привилегий небольших (как минимум запущенного shell).
    * Совсем без привилегии узнать юзера можно создав файл.
    * @return String Логин пользователя
    */
   function getCurrentUser(){
       //Вообще-то posix_get_login() должен возвращать правильный результат
       //но он этого не делает, когда sudo сукаблять!
       $processUser = posix_getpwuid(posix_geteuid());
	$login = $processUser['name'];
	return $login;
   }

   /**
     * Получение списка бекапов виртуальных машин
     * 
     * @param Bool $excludeEmpty Если true, то получает список файлов не нулевого размера (происходит при переполнении сервера)
     * @return String[] Список имен файлов
     */
   public function getVmsBackups($excludeEmpty = false)
   {
       $ftp_address = $this->config->getParam("ftp_address");
       $ftp_path = $this->config->getParam("ftp_path");
       $ftp_user = $this->config->getParam("ftp_user");
       $ftp_password = $this->config->getParam("ftp_password");
       
       $this->logger->console("Getting info from '$ftp_address$ftp_path'");
       $cmd = "ncftpls -l -u $ftp_user -p $ftp_password ftp://$ftp_address$ftp_path ";
       
       $cmdResult = $this->exec($cmd);
       
       $lines = explode("\n",$cmdResult);
       $result = [];
       foreach($lines as $line)
           if(trim($line))
           {
               $stripped = $this->stripFtpLine($line);
               if($excludeEmpty && $stripped["size"] == "0")
               {
                   continue;
               }
               $result[] = $stripped;
           }
       return $result;
   }
   
    /**
     * Получение списка имен бекапов виртуальных машин
     * 
     * @param Bool $excludeEmpty Если true, то получает список файлов не нулевого размера (происходит при переполнении сервера)
     * @return String[] Список имен файлов
     */
    public function getVmsBackupNames($excludeEmpty = false)
    {
        $backups = $this->getVmsBackups($excludeEmpty);
        $result = array();
        foreach($backups as $bak)
        {
            $result[] = $bak["file"];
        }
        return $result;
    }

    /**
     * Превращает информацию из строки выдачи ФТП в ассоциативный массив.
     * @param String $line Строка с данными по файлу
     * @return String[] Ассоциативные массив в полями ифнормарции о файле //("mode","a","b","c","size","month","day","time","file");
     */
    protected function stripFtpLine($line)
    {
        $result = array();
        $parts = explode(" ",$line);
        foreach($parts as $part)
        {
            if($part !== "")
            {
                $result[] = $part;
            }
        }
        $labels = array("mode","a","b","c","size","month","day","time","file");
        $final = array_combine($labels, $result);
        return $final;
    }
    
    /**
     * Отображает информацию о бекапах
     */
    public function showBackupInfo()
    {
        $backups = $this->getVmsBackupNames(true);

        $vms = $this->getVms();
        
        $i =0;
        foreach($vms as $vm)
        {
            $count = $this->getBackupsNumberForVm($vm,$backups);
            
            $additional = "";
            if($count >0)
            {
                //Получаем имена бекапов для машины и сортируем их по дате
                $entries = $this->getBackupsForVm($vm,$backups);
                //print_r($entries);
                usort($entries,function($a,$b){
                    return $this->getBackupTime($a) < $this->getBackupTime($b);
                });
                $time = $this->getBackupTime($entries[0]);
                $date = date("Y-m-d H:i:s",$time);
                $additional .= "Latest backup: $date";
            }
            echo ++$i.": $vm has $count backups available. $additional \n";
        }
        
    }
    
    /**
     * Удаляет старые бекапы для виртуальных машин
     * 
     * @param String $toKeep Количество последних бекапов, которые необходимо сохранить
     */
    public function removeOldBackups($toKeep = 3)
    {
        $this->logger->console("Removing latest backups except last '$toKeep'");
        $backups = $this->getVmsBackupNames();
        $vms = $this->getVms();
        foreach($vms as $vm)
        {
            //Получаем имена бекапов для машины и сортируем их по дате
            $entries = $this->getBackupsForVm($vm,$backups);
            usort($entries,function($a,$b){
                return $this->getBackupTime($a) < $this->getBackupTime($b);
            });
            
            $numberOfEntries = count($entries);
            //Если бекапов мало, то не удаляем старые
            if($numberOfEntries <= $toKeep)
            {
                $this->logger->console("Skipping '$vm' because it has '$numberOfEntries' entries.");
                continue;
            }
            //echo "$toKeep $numberOfEntries \n";
            for($i = $toKeep; $i < $numberOfEntries; $i++)
            {
                $this->deleteVmBackup($entries[$i]);
            }
        }
    }

    /**
     * Получение количества бекапов для конкретной виртуальной машины
     * 
     * @param String $vm Имя виртуальной машины
     * @param String[] $backups Список всех бекапов
     * @return Integer Количество бекапов для заданной виртуальной машины
     */
    public function getBackupsNumberForVm($vm, $backups)
    {
         $entries = $this->getBackupsForVm($vm, $backups);
         $count = count($entries);
         return $count;
    }

    /**
     * Получение бекапов для конкретной виртуальной машины
     * 
     * @param String $vm Имя виртуальной машины
     * @param String[] $backups Список всех бекапов
     * @return String[] Список бекапов для заданной виртуальной машины
     */
    public function getBackupsForVm($vm,$backups)
    {
        $result = [];
         foreach($backups as $line)
             if(strpos($line,$vm."_") === 0)
                 $result[] = $line;
        return $result;
    }

    /**
     * Получение времени бекапа 
     * 
     * @param String $backup Имя файла бекапа
     * @return Integer Время в формате Unix timestamp
     */
    protected function getBackupTime($backup)
    {
        $parts = explode("_",$backup);
        $reversed = array_reverse($parts);
        $timeAndExt = $reversed[0];
        $splat = explode(".",$timeAndExt);
        $time = $splat[0];
        $date = $reversed[1];
        $datetime = $date." ".$time;
        
       
        $time = strtotime($datetime);
        //print_r($datetime); die();
        return $time;
    }

    /**
     * Удаление бекапа виртуальной машины
     * 
     * @param String $filename Имя файла бекапа
     */
    public function deleteVmBackup($filename)
    {
        //Если какой-то дебил удалит папку бекапов будет очень обидно
        if (!$filename || !trim($filename))
            throw new Exception("Specify filename to delete");

        $this->logger->console("Removing backup '$filename'");
        $ftp_address = $this->config->getParam("ftp_address");
        $ftp_path = $this->config->getParam("ftp_path");
        $ftp_user = $this->config->getParam("ftp_user");
        $ftp_password = $this->config->getParam("ftp_password");

        // установка соединения
        $conn_id = ftp_connect($ftp_address);

        // вход с именем пользователя и паролем
        $login_result = ftp_login($conn_id, $ftp_user, $ftp_password);

        try
        {
            // попытка удалить файл
            if (ftp_delete($conn_id, $ftp_path.$filename))
            {
                $this->logger->console("'$filename' has been deleted");
               
            } else
            {
                $this->logger->console("Unable to remove '$file'");
            }
            ftp_close($conn_id);
        } catch (Exception $ex)
        {
            // закрытие соединения
            ftp_close($conn_id);
            throw $ex;
        }


    }

    /**
     * Удаляет пустые бекапы (если размер < 1000 байт). 
     * Использую 1000, а не 0 чтобы дебажить систему
     */
    public function removeEmptyBackups()
    {
        $this->logger->console("Removing empty backups");
        $backups = $this->getVmsBackups();
        foreach($backups as $backup)
        {
            $file = $backup["file"];
            //Если бекапов мало, то не удаляем старые
            if($backup["size"] < 1000)
            {
                $this->logger->console("Deleting '$file'");
                $this->deleteVmBackup($file);
                continue;
            }
        }
    }

    /**
     * Отображение списка бекапов и их размера
     */
    public function showBackupList()
    {
        $backups = $this->getVmsBackups($excludeEmpty);
        $result = array();
        foreach($backups as $bak)
        {
            echo $bak["file"].", size: ".$bak["size"],"\n";
        }
    }

    /**
     * Получение размера предыдущего бекапа для виртуальной машины
     * @param String $vm Имя виртуальной машины
     * @return Int Размер бекапа
     */
    public function getSizeOfPreviousBackup($vm)
    {
        $size = 0;
        $names = $this->getVmsBackupNames();
        $vmBakupNames = $this->getBackupsForVm($vm, $names);
        if(count($vmBakupNames) == 0)
        {
            return  0;
        }
        usort($vmBakupNames,function($a,$b){
                return $this->getBackupTime($a) < $this->getBackupTime($b);
            });
        
        $lastBackupName = $vmBakupNames[0];
        
        //Ищем информацию о данном бекапе
        $backups = $this->getVmsBackups();
        $filtered = array_filter($backups,function($a) use($lastBackupName){
           return $a["file"] == $lastBackupName; 
        });
        $values = array_values($filtered);
        $info = $values[0];
        
        $size = $info["size"];
        
        return $size;
    }

}

