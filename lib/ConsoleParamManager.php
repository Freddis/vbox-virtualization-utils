<?php

/**
 * Хелпер для консольных параметров.
 *
 * @author Sarychev Alexei <freddis336@gmail.com>
 */
class ConsoleParamManager
{

    /**
     * Список параметров
     * @var String[]
     */
    protected $params;

    /**
     * 
     * @param String[] $argv Массив параметров, желательно переменная окружения $argv. 
     */
    public function __construct($argv)
    {
	$this->params = $argv;
    }

    /**
     * Получение информации есть ли флаг в параметрах
     * 
     * @param  String $name Имя флага с префиксом. Например "--flag".
     * @return Bool True, если флаг есть.
     */
    public function hasFlag($name)
    {
	$params = $this->params;
	foreach ($params as $key => $param)
	    if ($param == $name)
		return true;
	return false;
    }

    /**
     * Получение значения параметра по его имени.
     * 
     * @param String Имя параметра с префиксом. Например "name".
     * @return String Значение параметра или NULL
     */
    public function getParam($name)
    {
	$params = $this->params;
	foreach ($params as $key => $param)
	{
	    if ($param == $name)
	    {
		if (!isset($params[$key + 1]))
		    return null;
		return $params[$key + 1];
	    }
	}
	return null;
    }

    }
