<?php

/**
 * Base class for daemons.
 *
 * @author Sarychev Alexei <alex@home-studio.pro>
 */
abstract class Daemon
{
    /**
     * Logger
     * @var Logger
     */
    protected $logger;

    /**
     * Is in debug mode
     * @var Bool
     */
    protected $debug;


    /**
     * @param Logger $logger
     * @param boolean $inDebug Режим дебага. Daemon is being executed in parent process, hence it also stops if the parent process stops.
     */
    public function Daemon(Logger $logger, $inDebug = false)
    {
        $this->logger = $logger;
        $this->debug = $inDebug;
    }

    /**
     * Starting daemon
     * @throws Exception Throws exception if cannot
     */
    public function start()
    {
        $name = __CLASS__;
        $this->logger->console("Starting '$name' daemon.");
        //running in  debug mode if necessary
        if ($this->debug)
            return $this->runDebug();

        // Creating a child process
        // all the code after pcntl_fork() will be executed in both child and parent processes
        $pid = pcntl_fork();
        if ($pid == -1) {
            // Couldn't create a child process
            $msg = "Unable to create child process. Exit.";
            $this->logger->console($msg);
            throw new Exception($msg);
        }
        if ($pid) {
            //the parent process, is going away
            $this->logger->console("Child process pid: $pid");
            return;
        }

        // And this code is the child process
        $childpid = getmypid();
        $this->run($childpid);
    }

    /**
     * Running daemon not in backgroupd.
     * After running it in debug we kill php, since in real conditions parent and child process cannot exchange any data.
     */
    protected function runDebug()
    {
        $this->logger->console("Debug mode. No child process created. Starting daemon code.");
        $pid = getmypid();
        $this->run($pid);
        exit;
    }

    /**
     * Running the daemon.
     * Here goes the code that is cycled in an endless while loop.
     *
     * @param Number $pid Process number
     */
    protected abstract function run($pid);


}
