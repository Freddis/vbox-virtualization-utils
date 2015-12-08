<?php
/**
 * Объект осуществляющий логирование в файл
 *
 * @author Sarychev Alexei <freddis336@gmail.com>
 */
class Logger
{
    /**
     * Флаг дублирования сообщений в консоль для отладки
     * @var Bool 
     */
    protected $copyToConsole;
    
    /**
     * Путь к файлу лога
     * @var String  
     */
    protected $pathToLogFile;
    
    /**
     * Конструктор
     * 
     * @param type $pathToLogFile Флаг дублирования сообщений в консоль для отладки
     * @param type $copyToConsole Дублирования сообщений в консоль для отладки
     * @throws Exception
     */
    public function Logger($pathToLogFile, $copyToConsole=false){
	$this->copyToConsole = $copyToConsole;
	$this->pathToLogFile = $pathToLogFile;
	
	$directory  = dirname($pathToLogFile);
	if(!is_dir($directory))
	    throw new Exception ("Directory does not exist: $directory");
	if(!is_writable($directory))
	    throw new Exception ("Directory isn't writable: $directory");	
	
	//Перехват варнингов для логирования
	set_error_handler(array($this, 'errorHandler'));
	//Перехват исключений для логирования
	set_exception_handler(array($this, 'exceptionHandler'));
    }
    
    /**
     * Обработчик варнингов
     * 
     * @param number $number Номер ошибки
     * @param String $message Сообщение
     * @param String $file Имя файла
     * @param String $line Номер строки
     */
    public function errorHandler($number, $message, $file, $line)
    {
	$this->console("Warning: '$message' in file $file:$line");
    }
    
    /**
     * Обработчик исключений
     * @param Exception $exeption Исключение
     */
    public function exceptionHandler(Exception $exeption){
	$message = $exeption->getMessage();
	$file = $exeption->getFile();
	$line = $exeption->getLine();
	$this->console("Exception: '$message' in file $file:$line");
    }
    
    /**
     * Логирование сообщения
     * @param String $msg Сообщение
     */
    public function log($msg){
	$msg = date("Y-m-d H:i:s").": ".$msg."\n";
	if($this->copyToConsole){
	    echo $msg;
	}
	if(!file_exists($this->pathToLogFile))
	    touch($this->pathToLogFile);
	file_put_contents($this->pathToLogFile, $msg, FILE_APPEND);
    }
    
    /**
     * Логирование сообщения и вывод его в консоль
     * @param String $msg Сообщение
     */
    public function console($msg){
	$tmp = $this->copyToConsole;
	$this->copyToConsole = true;
	$this->log($msg);
	$this->copyToConsole = $tmp;
    }
}
