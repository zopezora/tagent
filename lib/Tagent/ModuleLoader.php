<?php
/**
 * Module class loader, part of Tagent
 * namespace prefix '\Module_***\'
 * @package Tagent
 */
namespace Tagent;

class ModuleLoader
{
    /**
     * @static
     * @var object
     */
    public static $loader = null;

    /**
     * @var array agent direcrory
     */
    private $agentDirectorys = array();
    /**
     * init
     * @param  string $agentDirectory 
     * @return object
     */
     public static function init($agentDirectory)
    {
        if (! isset(self::$loader )){
            self::$loader = new self($agentDirectory);
        }
        return self::$loader;
    }
    /**
     * constructor
     * @param  string $agentDirectory 
     * @return void
     */
    private function __construct($agentDirectory)
    {
        if (isset($agentDirectory)) {
            $this->agentDirectorys = $agentDirectory;
        }
        spl_autoload_register(array($this, 'LoadModule'), true, true);
    }
    /**
     * Registered with the spl_autoload.
     * Top-level namespace 'Module_***' only.   '_' no replacement
     * @param  string $className 
     * @return true|void
     */
    public function LoadModule($className)
    {
        if ( $className[0] != 'M' || substr($className, 0, 7) != 'Module_') {
            return;
        }
        $className = ltrim($className, '\\');
        $fileName  = '';
        $namespace = '';
        if ($lastNsPos = strripos($className, '\\')) {
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
        }
        $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace).DIRECTORY_SEPARATOR;
        $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className).'.php'; // psr-0
//      $fileName .= $className;

        foreach ($this->agentDirectorys as $dir) {
            if (is_readable($dir.$fileName)) {
                require $dir.$fileName;
                return true;
            }
        }
    }

}
