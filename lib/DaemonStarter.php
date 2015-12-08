<?php

/**
 * Description of DaemonStarter
 * @see Daemon
 * @author Sarychev Alexei <freddis336@gmail.com>
 */
abstract class Daemon
{

    protected $logger;

    public function Daemon(Logger $logger)
    {
	$this->logger = $logger;
    }

    public abstract function run();

    public function start()
    {

	$this->logger->log("Starting vmutils daemon");

	// Создаем дочерний процесс
	// весь код после pcntl_fork() будет выполняться
	// двумя процессами: родительским и дочерним
	$pid = pcntl_fork();
	if ($pid == -1)
	{
	    // Не удалось создать дочерний процесс
	    $logger->log("Can't create child process");
	    return FALSE;
	} elseif ($pid)
	{
	    // Этот код выполнится родительским процессом
	    exit;
	} else
	{
	    // А этот код выполнится дочерним процессом
	    $logger->log("Child process id: " . getmypid());
	    $this->run();
	}
	return TRUE;
    }

}
