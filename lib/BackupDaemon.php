<?php

/**
 * Daemon for backing up virtual machines
 * Backups are being made every day at the same time
 *
 * @author Sarychev Alexei <alex@home-studio.pro>
 */
class BackupDaemon extends Daemon
{

    /**
     * Daemon's wake up interval
     * @var Number
     */
    protected $interval = 30 * 60;

    /**
     * Hour in 2-digit format at which backups are going to be made
     * @var String
     */
    protected $backupHour = "00";

    /**
     * Daemon's config
     * @var Config
     */
    protected $config;

    /**
     * Номер итераций в дебаге.
     * @var Number
     */
    protected $debugIteration = 0;

    /**
     * @param Config $config Configuration
     * @param Logger $log Logger
     * @param boolean $debugFlag Debug mode. In this mode time passes quckly and heavy operations become lightweight.
     */
    public function __construct(Config $config, Logger $log, $debugFlag = false)
    {
        parent::__construct($log, $debugFlag);
        $this->config = $config;
    }

    /**
     * Starting daemon
     * @param $pid Process ID
     */
    protected function run($pid)
    {
        while (true) {
            $hour = $this->getHour();
            $this->logger->log("Awoken at $hour");
            $timeToSleep = $this->interval;
            //If it's the defined hour then make backups
            if ($hour == $this->backupHour) {
                $this->backup();
                //If backup was too fast we need to wait for the next hour
                if ($this->getHour() == $this->backupHour)
                    $timeToSleep = ((60 - intval($this->getMinute())) + 1)*60;
                $this->logger->log("Sleep after backup: {$timeToSleep}s.");
            }
            //in debug we sleep just for a little bit
            if ($this->debug)
                $timeToSleep = 1;
            sleep($timeToSleep);
        }
    }

    /**
     * Getting current hour in 2-digit format
     * @return String
     */
    protected function getHour()
    {
        $hour = date("H");
        //В дебаге, мы просто итерируем часы с каждым вызовом
        if ($this->debug) {
            $hour = sprintf("%02d", ++$this->debugIteration);
            if ($this->debugIteration >= 24)
                $this->debugIteration = 0;
        }
        return $hour;
    }

    /**
     * Getting current minute in 2-digit format
     * @return String
     */
    protected function getMinute()
    {
        $minute = date("i");
        return $minute;
    }

    /**
     * Making a backup
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
