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
        spl_autoload_register(array($this,'LoadModule'));
    }
    /**
     * Registered with the spl_autoload.
     * Top-level namespace 'Module_***' only.   '_' no replacement
     * @param  string $className 
     * @return true|void
     */
    public function LoadModule($className)
    {
        $className = ltrim($className, '\\');
        $fileName  = '';
        $namespace = '';
        if ($lastNsPos = strripos($className, '\\')) {
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
        }
        if ( strpos($namespace,'Module_')==0 ) {
            $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace).DIRECTORY_SEPARATOR;
            $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className).'.php'; // psr-0
//            $fileName .= $className;

            foreach ($this->agentDirectorys as $directory) {
                if (is_readable($directory.$fileName)) {
                    require $directory.$fileName;
                    return true;
                }
            }
        }
    }

}
