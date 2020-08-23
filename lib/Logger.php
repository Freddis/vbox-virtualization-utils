<?php

/**
 * Logger that logs to a file
 *
 * @author Sarychev Alexei <alex@home-studio.pro>
 */
class Logger
{
    /**
     * If true copies the messages to STDOUT
     * @var Bool
     */
    protected $copyToConsole;

    /**
     * Path to the log file
     * @var String
     */
    protected $pathToLogFile;

    /**
     * @param boolean $pathToLogFile Path to the log file
     * @param bool $copyToConsole If true copies the messages to stdout
     * @throws Exception
     */
    public function __construct($pathToLogFile, $copyToConsole = false)
    {
        $this->copyToConsole = $copyToConsole;
        $this->pathToLogFile = $pathToLogFile;

        $directory = dirname($pathToLogFile);
        if (!is_dir($directory))
            throw new Exception ("Directory does not exist: $directory");
        if (!is_writable($directory))
            throw new Exception ("Directory isn't writable: $directory");

        //Intercepting warnings
        set_error_handler(array($this, 'errorHandler'));
        //Intercepting exceptions
        set_exception_handler(array($this, 'exceptionHandler'));
    }

    /**
     * Warning handler
     *
     * @param number $number Error number
     * @param String $message MEssage
     * @param String $file Filepath
     * @param String $line Line number
     */
    public function errorHandler($number, $message, $file, $line)
    {
        $this->console("Warning: '$message' in file $file:$line");
    }

    /**
     * Exception handler
     * @param Exception $exeption Исключение
     */
    public function exceptionHandler(Exception $exeption)
    {
        $message = $exeption->getMessage();
        $file = $exeption->getFile();
        $line = $exeption->getLine();
        $this->console("Exception: '$message' in file $file:$line");
    }

    /**
     * Logs a message
     * @param String $msg Message
     */
    public function log($msg)
    {
        $msg = date("Y-m-d H:i:s") . ": " . $msg . "\n";
        if ($this->copyToConsole) {
            echo $msg;
        }
        if (!file_exists($this->pathToLogFile))
            touch($this->pathToLogFile);
        file_put_contents($this->pathToLogFile, $msg, FILE_APPEND);
    }

    /**
     * Log a message and show it in STDOUT
     * @param String $msg Message
     */
    public function console($msg)
    {
        $tmp = $this->copyToConsole;
        $this->copyToConsole = true;
        $this->log($msg);
        $this->copyToConsole = $tmp;
    }
}
