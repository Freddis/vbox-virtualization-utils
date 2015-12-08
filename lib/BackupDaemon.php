<?php

/**
 * Демон для резервного копирования виртуальных машин.
 * Делается это каждый день, в определенный час.
 *
 * @author Sarychev Alexei <freddis336@gmail.com>
 */
class BackupDaemon extends Daemon
{

    /**
     * Интервал в котором демон будет просыпаться
     * @var Number
     */
    protected $interval = 30 * 60;

    /**
     * Час в двузначном формате, в который надо делать бекап
     * @var String 
     */
    protected $backupHour = "04";

    /**
     * Настройки демона
     * @var Config
     */
    protected $config;

    /**
     * Номер итераций в дебаге.
     * @var Number 
     */
    protected $debugIteration = 0;

    /**
     * Конструктор
     * @param Config $config Настройки демона
     * @param Logger $log Объект логирования
     * @param type $inDebug Режим дебага. В нем время летит очень быстро, а тяжелые операции становятся легкими.
     */
    public function BackupDaemon(Config $config, Logger $log, $debugFlag = false)
    {
	parent::__construct($log, $debugFlag);
	$this->config = $config;
    }

    /**
     * Запуск демона
     * @param $pid Номер процесса
     */
    protected function run($pid)
    {
	while (true)
	{
	    //Если наступил нужный час, то запускаем бекап
	    $hour = $this->getHour();
	    $this->logger->log("Awoken at $hour");
	    $timeToSleep = $this->interval;
	    if ($hour == $this->backupHour)
	    {
		$this->backup();
		//Если бекап произошел сликом быстро, то надо подождать до следующего часа
		if ($this->getHour() == $this->backupHour)
		    $timeToSleep = (60 - intval($this->getMinute())) + 1;
		$this->logger->log("Sleep after backup: {$timeToSleep}s.");
	    }
	    //В дебаге считаем все как есть, но спим мало
	    if ($this->debug)
		$timeToSleep = 1;
	    sleep($timeToSleep);
	}
    }

    /**
     * Получение текущего часа в двузначном формате
     * @return String
     */
    protected function getHour()
    {
	$hour = date("H");
	//В дебаге, мы просто итерируем часы с каждым вызовом
	if ($this->debug)
	{
	    $hour = sprintf("%02d", ++$this->debugIteration);
	    if ($this->debugIteration >= 24)
		$this->debugIteration = 0;
	}
	return $hour;
    }

    /**
     * Получение текущей минуты в двузначном формате
     * @return String
     */
    protected function getMinute()
    {
	$minute = date("");
	return $minute;
    }

    /**
     * Произведение бекапа
     */
    protected function backup()
    {
	$this->logger->log("Backup daemon making backup");
	$helper = new Helper($this->config, $this->logger);
	$helper->debug = $this->debug;
	$helper->backupVms();
	$this->logger->log("Backup daemon done");
    }

}
