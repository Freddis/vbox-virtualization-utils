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
	$timeout = $this->config->getParam("timeout_between_vms");
	$this->logger->console("Vms to start: ".$content);
	$vms = explode(",",$content);
	foreach ($vms as $vm){
	    $this->startVm($vm);
	    $this->logger->console("Waiting {$timeout}s.");
	    sleep($timeout);
	}
	    
	
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
     * @param String $path Путь к папке, где будут храниться временные файлы
     * @throws Exception
     */
    public function backupVms($path){
	if(!is_writable($path))
	    throw new Exception ("File either not writable or not exists: $path");
        $this->logger->console("Starting backing up\n");
	$starttime = time();
	$foldername = $this->createBackupFolder($path);
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
	
	$this->logger->log("--exec-- ".$command);
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
    * 
    * @return String Путь к созданной папке.
    */
   public function createBackupFolder($path){
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
   }
}

