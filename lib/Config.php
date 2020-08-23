<?php

/**
 * Reads config files.
 *
 * Format: variable=value
 * Delimiter: new line
 *
 * @author Sarychev Alexei <alex@home-studio.pro>
 */
class Config
{
    /**
     * Config filepath
     * @var String
     */
    protected $pathToFile;

    /**
     * Assoc array of params
     * @var String[]
     */
    protected $params = array();

    /**
     * @param String $file Path to the config file
     * @throws Exception
     */
    public function __construct($file)
    {
        $this->pathToFile = $file;
        if (!is_readable($file))
            throw new Exception ("Wrong path to file given: $file");
        $this->parseConfigFile();
    }

    /**
     * Parses the config file, creates array of params
     *
     * @throws Exception
     */
    public function parseConfigFile()
    {
        try {
            $content = file_get_contents($this->pathToFile);
            $parts = explode("\n", $content);
            foreach ($parts as $line) {
                //Отделяем комментарии
                $real = explode("#", $line)[0];
                //Пропускаем пустые строки
                if (empty(trim($real)))
                    continue;
                $splat = explode("=", $real);
                $name = trim($splat[0]);
                $value = trim($splat[1]);
                if (!empty($name)) {
                    $this->params[$name] = $value;
                }
            }
        } catch (Exception $e) {
            throw  new Exception("Wrong file formatting");
        }
    }

    /**
     * Getting a parameter
     *
     * @param String $name Param name
     * @param Bool $nullableFlag If True, then returns NULL if param doesn't exist. Otherwise throws an exception
     * @return String Value of the param
     * @throws Exception
     */
    public function getParam($name, $nullableFlag = false)
    {
        if (isset($this->params[$name]))
            return $this->params[$name];

        if ($nullableFlag !== true)
            throw new Exception ("Param '$name' not found.");

        return null;
    }
}
