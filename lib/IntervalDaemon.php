<?php

/**
 * Демон для исполнения команд с определенным интервалом
 *
 * @author Sarychev Alexei <freddis336@gmail.com>
 */
abstract class IntervalDaemon extends Daemon
{
    
    /**
     * Интервал в котором демон будет просыпаться, если он врежиме дебага
     * 
     * @var Number 
     */
    public $debugInterval = 1;

    /**
     * Интервал в котором демон будет просыпаться
     * @var Number
     */
    protected $interval = 60;

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
    public function __construct(Config $config, Logger $log, $debugFlag = false)
    {
	parent::__construct($log, $debugFlag);
	$this->config = $config;
	$confInterval  = $config->getParam("interval", true);
	$this->interval = $confInterval != null ? $confInterval : $this->interval;
    }

    /**
     * Запуск демона
     * @param $pid Номер процесса
     */
    protected function run($pid)
    {
	
	while (true)
	{ 
	    $this->logger->log("Awoken ".$pid);
	    
	    $timeToSleep = $this->interval;
	    //В дебаге считаем все как есть, но спим мало
	    if ($this->debug)
		$timeToSleep = $this->debugInterval;
	    
	    $this->executeAction();
	    sleep($timeToSleep);
	}
    }

    protected abstract function executeAction();
    
    
    

}
