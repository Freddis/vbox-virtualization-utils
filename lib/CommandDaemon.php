<?php
/**
 * Интервальный демон, который запускает команды
 * @author Sarychev Alexei <freddis336@gmail.com>
 */
class CommandDaemon extends IntervalDaemon
{ 
    protected function executeAction()
    {
	$cmd = $this->config->getParam("cmd", true);
	$cmd = str_replace("{DIR}", __DIR__, $cmd);
	$this->logger->log("Exec: $cmd");
	exec($cmd,$out);
	$formatted = "Out: \n".join("\n", $out);
	$this->logger->log($formatted);
	$this->logger->log("Done.");
    }


}
