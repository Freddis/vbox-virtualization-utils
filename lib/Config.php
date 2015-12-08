<?php
/**
 * Объект для чтения простых конфигов.
 * Формат: variable=value
 * Разделитель: переход на новую строку
 *
 * @author Sarychev Alexei <freddis336@gmail.com>
 */
class Config
{
    /**
     * Путь к файлу конфигов
     * @var String 
     */
    protected $pathToFile;
    
    /**
     * Ассоциативный массив параметров
     * @var String[]
     */
    protected $params = array();
    
    /**
     * Конструктор
     * 
     * @param String $file Путь к фалу с параметрами
     * @throws Exception
     */
    public function Config($file){
	$this->pathToFile = $file;
	if(!is_readable($file))
	    throw new Exception ("Wrong path to file given: $file");
	$this->parseConfigFile();
    }
    
    /**
     * Парсинг конфиг файла на параметры
     * 
     * @throws Exception
     */
    public function parseConfigFile(){
	try{
	$content = file_get_contents($this->pathToFile);
	$parts = explode("\n", $content);
	foreach ($parts as $line){
		//Отделяем комментарии
		$real = explode("#", $line)[0];
		//Пропускаем пустые строки
		if(empty(trim($real)))
		    continue;
		$splat = explode("=", $real);
		$name = trim($splat[0]);
		$value = trim($splat[1]);
		if(!empty($name)){
		    $this->params[$name] = $value;
		}
	    }
	}
	catch (Exception $e){
	    throw  new Exception("Wrong file formatting");
	}
    }
    
    /**
     * Получение параметра.
     * 
     * @param String $name Имя параметра
     * @param Bool $nullableFlag Если True, то в случае отсутствия параметр вернется NULL. Иначе будет выданно исключение.
     * @return String Значение параметра
     * @throws Exception
     */
    public function getParam($name,$nullableFlag = false){
	if(isset($this->params[$name]))
	    return $this->params[$name];
	
	if($nullableFlag !== true)
	    throw new Exception ("Param '$name' not found.");
	
	return null;
    }
}
