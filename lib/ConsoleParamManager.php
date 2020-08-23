<?php

/**
 * Helper to manage command line parameters
 *
 * @author Sarychev Alexei <alex@home-studio.pro>
 */
class ConsoleParamManager
{

    /**
     * Assoc array of params
     * @var String[]
     */
    protected $params;

    /**
     *
     * @param String[] $argv Array of params, preferably $argv.
     */
    public function __construct($argv)
    {
        $this->params = $argv;
    }

    /**
     * Gets if the flag is present
     *
     * @param String $name Name of the flag with prefix, like this:  "--flag".
     * @return Bool True, if the flag is present.
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
     * Getting the parameter by name.
     *
     * @param String Name of parameter with prefix, like this:  "-name" or "name".
     * @return String The value or NULL if parameter doesn't exist
     */
    public function getParam($name)
    {
        $params = $this->params;
        foreach ($params as $key => $param) {
            if ($param == $name) {
                if (!isset($params[$key + 1]))
                    return null;
                return $params[$key + 1];
            }
        }
        return null;
    }

}
