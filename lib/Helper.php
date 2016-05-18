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
	$foldername = $this->createBackupFolder();
	$this->exportVms($foldername);
	$this->uploadToFTP($foldername);
	$this->deleteFolder($foldername);

	$time = time() - $starttime;
	$this->logger->console("Backup complete in ".$time."s.");
	
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
       $this->exec("rm -rf ".$foldername);
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
       $this->debugExec($cmd);
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
	   $this->exportVm($vm,$foldername);
       $time = time() - $starttime;
       $this->logger->console("Export done in ".$time."s.");
   }

   /**
    * Экспорт одной виртуальной машины. При необходимости машина выключается, затем ее работа будет возобновлена.
    * @param String $name Имя виртуальной машины
    * @param String $path Путь к папке в которую ее нужно экспортировать
    */
   public function exportVm($name,$path)
   {
       $this->logger->console("Exporting ".$name." to ".$path);
       $runningvms = $this->getRunningVms();
       $isRunning = in_array($name,$runningvms);
       if($isRunning){
	   $this->logger->console("VM is running. Powering off.");
	   $this->debugExec("VBoxManage controlvm ".$name." poweroff");
       }
       $this->logger->console("Exporting...");
       $this->debugExec("VBoxManage export ".$name." -o ".$path."/".$name.".ova");
       
       if($isRunning){
	   $this->logger->console("Starting vm back again.");
	   $this->debugExec("VBoxManage startvm ".$name." --type headless");
       }
   }

   /**
    * Создание папки для бекапов. Название содержит текущую дату.
    */
   public function createBackupFolder(){
      $path = $this->config->getParam("path_to_temp_folder");
      if(!is_writable($path))
	    throw new Exception ("File either not writable or not exists: $path");   
      $name = date("Y-m-d_H:i");
      $path = $path."/".$name;
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
}

