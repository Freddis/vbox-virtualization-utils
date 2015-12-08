<?php

/**
 * Базовый класс для демонов. Демон - это фоновые процессы в Unix.
 * 
 * @author Sarychev Alexei <freddis336@gmail.com>
 */
abstract class Daemon
{
    /**
     * Объект для логирования
     * @var Logger
     */
    protected $logger;

    /**
     * Режим дебага.	
     * @var Bool 
     */
    protected $debug;
    
   
    /**
     * Конструктор
     * @param Logger $logger
     * @param type $inDebug Режим дебага. Демон запускается в процессе родителя. То есть при завершении основного скрипта демон тоже завершится.
     */
    public function Daemon(Logger $logger, $inDebug=false)
    {
	$this->logger = $logger;
	$this->debug = $inDebug;
    }
    
    /**
     * Запуск демона. 
     * @return Undefined Запуск демона не может ничего возвращать. Демон - это конечная программа, поэтому должен все необходимые действия делать сам.
     * @throws Exception Исключение в случае неудачи запуска демона.
     */
    public function start()
    {	
	$name = __CLASS__;
	$this->logger->console("Starting '$name' daemon.");
	//Запуск в режиме дебага, если необходимо.
	if($this->debug)
	    return $this->runDebug();
	
	// Создаем дочерний процесс
	// весь код после pcntl_fork() будет выполняться
	// двумя процессами: родительским и дочерним
	$pid = pcntl_fork();
	if ($pid == -1)
	{
	    // Не удалось создать дочерний процесс
	    $msg = "Unable to create child process. Exit.";
	    $logger->console($msg);
	    throw new Exception($msg);
	}
	if ($pid)
	{
	    //родительский процесс уходит, так как мы работаем в фоновом режиме
	    $this->logger->console("Child process pid: $pid");
	    return;
	} 
	
	// А этот код выполнится дочерним процессом
	$childpid = getmypid();
	$this->run($childpid);
	//Так как демон конечная программа, выполнение php я остановлю насильно
	exit;
    }
    
    /**
     * Выполнение демона в режиме дебага. Выполнение его не в фоновом режиме.
     * Затем убиваем PHP, потому что в реальных условиях родительский процесс и дочерний не могут просто так обмениваться информацией.
     */
    protected function runDebug(){
	$this->logger->console("Debug mode. No child process created. Starting daemon code.");
	$pid =  getmypid();
	$this->run($pid);
	//Даже в режиме дебага надо понимать, что демон это конечная программа.
	exit;
    }
    /**
     * Исполнение кода демона.
     * Тут начинается исходный код демона, зацикливаюшийся в While.
     * @param Number $pid Номер процесса демона
     */
    protected abstract function run($pid);   
    
    
}
