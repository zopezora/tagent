<?php
/**
 * Agent, part of Tagent
 * tag parser, module control, Object locator
 * @package Tagent
 */
namespace Tagent;

use Tagent\AbstractModule;
use Tagent\FactoryInterface;
use Tagent\RefreshModuleInterface;
use Tagent\CloseModuleInterface;

class Agent
{
    // config default
    const AGENT_TAG        = "ag";
    const AGENT_DIRECTORY  = "ag/";
    const DEBUG            = false;
    const SHUTDOWN_DISPLAY = false;
    const LINE_OFFSET      = 0;
    const TEMPLATE_EXT     = ".tpl";
    const LOG_REPORTING    = E_ALL;

    /**
     * @static
     * @var object  static for singleton
     */
    protected static $selfInstance = null;
    /**
     * @var array  
     */
    protected $configs = array();
    /**
     * @var object   Object ModuleLoader
     */
    protected $loader = null;
    /**
     * @var array    ['modulename']['instance']/['objects']/['variables'] 
     */
    protected $modules = array();
    /**
     * @var string current work directory for callback out buffer 
     */
    protected $cwd = null;
    /**
     * @var callback ob_start callback
     */
    protected $buffercallback = null;
    /**
     * @var array output buffer object contenaire
     */
    protected $buffers = array();
    /**
     * @var object  logger object
     */
    protected $logger = null;
    /**
     * @var integer  line counter
     */
    public $line = 0;
    /**
     * @var array   $pdo  object container
     */
    public $db = array();
    /**
     * @var integer  loglevel
     */
    protected $loglevel = array(
                                E_ERROR   => 'ERROR',   // 1
                                E_WARNING => 'WARNING', // 2
                                E_PARSE   => 'Parse',   // 4
                                E_NOTICE  => 'NOTICE'   // 8
                               );
    // init config   ------------------------------------------------
    /**
     * initialize Agent return singleton instance. 
     * @static 
     * @param  array $config 
     * @param  object $loader    composer classloader 
     * @return object
     */
    public static function init(array $config = array())
    {
        if (! isset(static::$selfInstance)){
            static::$selfInstance = new static($config);
        }
        return static::$selfInstance;
    }
    /**
     * singleton instance
     * @return object self instance
     */
    public static function getInstance()
    {
        return static::$selfInstance;
    }
    /**
     * singleton instance
     * @return object self instance
     */
    public static function self()
    {
        return static::$selfInstance;
    }
    /**
     * configure , out buffer start
     * @param array $config 
     * @param bool  $bufferstart 
     * @access protected
     */
    protected function __construct(array $config = array())
    {
        // set config default 
        $this->configs = array (
            "agent_tag"         => self::AGENT_TAG,
            "agent_directories" => array (self::AGENT_DIRECTORY),
            "debug"             => self::DEBUG,
            "shutdown_display"  => self::SHUTDOWN_DISPLAY,
            "line_offset"       => self::LINE_OFFSET,
            "template_ext"      => self::TEMPLATE_EXT,
            "log_reporting"     => self::LOG_REPORTING,
            "db"                => array(),
        );
        // agent direcrories
        if (array_key_exists('agent_directories', $config) && ! is_array($config['agent_directories'])) {
            $config['agent_directories'] = (array) $config['agent_directories'];
        }
        // Config override
        $this->configs = Utility::arrayOverride( $this->configs, $config );
        $this->configs['agent_directories'] = array_unique($this->configs['agent_directories']);
        // Logger
        $this->debug($this->configs['debug']);
        // Module Autoloader
        $this->loader = ModuleLoader::init($this->configs['agent_directories']);
        // currnt directory for outbuffer callback
        $this->cwd = getcwd();
        // shutdown display
        $this->configs['shutdown_display'] = Utility::boolStr($this->configs['shutdown_display'], self::SHUTDOWN_DISPLAY);
        if ($this->configs['shutdown_display']) {
            $callback = array($this, 'obCallback');
            ob_start($callback);
            ob_implicit_flush(false);
            register_shutdown_function( array($this,'shutdown'));
        }
    }
    /**
     * protected for singleton
     * @return void
     */
    protected function __clone()
    {
    }
    /**
     * return config.
     * @param  string $key
     * @return array
     */
    public function getConfig($key = null)
    {
        if (isset($key)) {
            return (isset($this->configs[$key])) ? $this->configs[$key] : null ;
        }
       return $this->configs;
    }
    /**
     * debug  set/get
     * @param  bool|string|null  $bool  true|false  'on|off' / 'yes|no' / 'y|n' / null
     * @return bool true|false
     */
    public function debug($debug = null)
    {
        if (! is_null($debug)) {
            $this->configs['debug'] = Utility::boolStr($debug, false);
            if ($this->configs['debug'] && is_null($this->logger)) {
                $this->logger = new Logger();
            }
        } else {
            return Utility::boolStr($this->configs['debug'], false);
        }
    }
    /**
     * log reporting level
     * @param  integer $level 
     * @return integer
     */
    public function log_reporting($level = null)
    {
        if (isset($level)) {
            $this->configs['log_reporting'] = $level;
        }
        return $this->configs['log_reporting'];
    }
    /**
     * get line number of parse
     * @return integer
     */
    public function getLine()
    {
        return $this->line;
    }
    // Variables --------------------------------------------------------------------
    /**
     * get variable .  no exists key return false.
     * @param  string  $name
     * @param  string  $module
     * @param  integer $bk caller backtrace number for log
     * @return mixed|null
     */
    public function getVariable($name = null, $module = 'GLOBAL', $bk = 0)
    {
        if (! is_null($name) && ! is_string($name)) {
            $name = Utility::getValueOrType($name);
            $this->log(E_WARNING, "getVariable({$name},'{$module}') Name must be a string", true,'AGENT_VARIABLE', $bk);
            return null;
        }
        if (! isset($this->modules[$module])) {
            $this->log(E_WARNING, "getVariable('{$name}','{$module}') Module not open yet", true,'AGENT_VARIABLE', $bk);
            return;
        }
        if (is_null($name)) {
            return $this->modules[$module]['variables'];
        }
        if (isset($this->modules[$module]['variables'][$name])) {
            return $this->modules[$module]['variables'][$name];
        }
        $this->log(E_WARNING,"getVariable('{$name}','{$module}') Not Found.", true, 'AGENT_VARIABLE', $bk);
        return null;
    }
    /**
     * set/unset variables  always override
     * @param string $name 
     * @param mixed $var 
     * @param  string  $module
     * @param  integer $bk caller backtrace number for log
     * @return void
     */
    public function setVariable($name, $value, $module = 'GLOBAL', $bk = 0)
    {
        if (is_null($name) || ! is_string($name)) {
            $name = Utility::getValueOrType($name);
            $v    = Utility::getValueOrType($value);
            $this->log(E_WARNING, "setVariable({$name},{$v},'{$module}') Name must be a string", true,'AGENT_VARIABLE', $bk);
            return;
        }
        if (! isset($this->modules[$module])) {
            $v = Utility::getValueOrType($value);
            $this->log(E_WARNING, "setVariable('{$name}',{$v},'{$module}') Module not open yet", true, 'AGENT_VARIABLE', $bk);
            return;
        }
        if (is_null($value) && isset($this->modules[$module]['variables'][$name])) {
            unset ($this->modules[$module]['variables'][$name]);
            return;
        }
        $this->modules[$module]['variables'][$name] = $value;
        return;
    }
    /**
     * override set Variables by array  
     * @param  array   $array 
     * @param  string  $module
     * @param  integer $bk caller backtrace number for log
     * @return void
     */
    public function setVariablesByArray(array $array, $module = 'GLOBAL', $bk = 0)
    {
        if (! isset($this->modules[$module])) {
            $array = Utility::getValueOrType($array);
            $this->log(E_WARNING, "setVariableByArray({$array},'{$module}'') Module not open yet", true,'AGENT_VARIABLE', $bk);
            return;
        }
        $this->modules[$module]['variables'] = $array;
    }
    // Object Locator --------------------------------------------------------------------
    /**
     * get object
     * @name   string $name 
     * @param  string  $module
     * @param  integer $bk caller backtrace number for log
     * @return object
     */
    public function get($name, $module = 'GLOBAL', $bk = 0)
    {
        if (! isset($name) || ! is_string($name)) {
            $name = Utility::getValueOrType($name);
            $this->log(E_WARNING, "get('{$name}','{$module}') Name must be a string", true, 'AGENT_LOCATOR', $bk);
            return null;
        }
        if (! isset($this->modules[$module])) {
            $this->log(E_WARNING,"get('{$name}','{$module}') module is not open yet.", true, 'AGENT_LOCATOR', $bk);
            return null;
        }
        if (! isset($this->modules[$module]['objects'][$name])) {
            $this->log(E_WARNING,"get('{$name}','{$module}') Not Found.", true, 'AGENT_LOCATOR', $bk);
            return null;
        }
        $object = $this->modules[$module]['objects'][$name];
        if ($object instanceof FactoryInterface) {
            $this->modules[$module]['objects'][$name] = $object->factory();
            $this->log(E_NOTICE,"get('{$name}','{$module}') call ".get_class($object)."->factory()", true, 'AGENT_LOCATOR', $bk);
            return $this->modules[$module]['objects'][$name];
        }
        return $object;
    }
    /**
     * set object
     * @param  string $name 
     * @param  object $object   if null , unset Object
     * @param  string  $module
     * @param  integer $bk caller backtrace number for log
     * @return void
     */
    public function set($name, $object, $module = 'GLOBAL', $bk = 0)
    {
        if (! isset($name) || ! is_string($name)) {
            $name = Utility::getValueOrType($name);
            $v = Utility::getValueOrType($object);
            $this->log(E_WARNING, "set({$name},{$v},'{$module}') Name must be a string", true, 'AGENT_LOCATOR', $bk);
            return;
        }
        if (! isset($this->modules[$module])) {
            $v = Utility::getValueOrType($object);
            $this->log(E_WARNING, "set('{$name}', {$v}, '{$module}') module is not open yet", true, 'AGENT_LOCATOR', $bk);
            return;
        }
        if (is_null($object) && isset($this->modules[$module]['objects'][$name])) { 
            unset ($this->modules[$module]['objects'][$name]);
            return;
        }
        $this->modules[$module]['objects'][$name] = $object;
    }
    /**
     * has object
     * @param  string  $name 
     * @param  string  $module
     * @param  integer $bk caller backtrace number for log
     * @return bool   true|false
     */
    public function has($name, $module = 'GLOBAL', $bk = 0)
    {
        if (! isset($name) || ! is_string($name) ) {
            $name = Utility::getValueOrType($name);
            $this->log(E_WARNING,"has('{$name}', '{$module}') Name must be a string", true, 'AGENT_LOCATOR', $bk);
            return false;
        }
        if (! isset($this->modules[$module])) {
            $this->log(E_WARNING,"has('{$name}','{$module}') module is not open yet.", true, 'AGENT_LOCATOR', $bk);
            return false;
        }
        return (isset($this->modules[$module]['objects'][$name])) ? true : false;
    }
    // pdo --------------------------------------------------------------------
    // configs[pdo][name]   [dsn][username][password][options]

    /**
     * db return PDO handle
     * @param type $name 
     * @return object|false
     */
    public function db($name = null)
    {
        $name = (isset($name)) ? $name : 'default' ;
        if (isset($this->db[$name])) {
            return $this->db[$name];
        }
        $dbConfig = $this->getConfig('db');
        if (isset($dbConfig[$name])) {
            $dbConfig = $dbConfig[$name];
            $dsn      = (isset($dbConfig['dsn']))      ? $dbConfig['dsn']      : '';
            $user     = (isset($dbConfig['user']))     ? $dbConfig['user']     : '';
            $password = (isset($dbConfig['password'])) ? $dbConfig['password'] : '';
            $options  = (isset($dbConfig['options']))  ? $dbConfig['options']  : array();

            if ($dsn == '') {
                $this->log(E_ERROR,"Not found config dsn [db][{$name}][dsn]", true, 'AGENT_DB', 0);
                return false;
            }
            try {
                $dbh = new \PDO($dsn, $user, $password, $options);
            } catch (\PDOException $e) {
                $this->log(E_ERROR,$e->getMessage(), true, 'AGENT_DB', 0);
                return $this->db[$name] = false;
            }
            $this->db[$name] = $dbh;
            $this->log(E_NOTICE,"Connect DB '{$name}': dsn={$dsn}", true, 'AGENT_DB', 0);
            return $this->db[$name] = $dbh;
        } else {
            $this->log(E_ERROR,"Not found DB config {$name}", true, 'AGENT_DB', 0);
            return $this->db[$name] = false;
        }
    }
    // Module Control --------------------------------------------------------------------
    /**
     * Module namespace
     * @param  string $module 
     * @return string
     */
    public function getModuleNamespace($module)
    {
        return "Module_".$module;
    }
    /**
     * Open Module class
     * @param  string $module
     * @param  array 
     * @return object|false
     */
    protected function openModule($module, $params = array())
    {
        if (is_null($instance = $this->getModule($module))) {
            $this->log(E_NOTICE, "Open Module {$module}", true, $module);
            $instance = $this->createModule($module, $params);
        }
        return $instance;
    }
    /**
     * reopen module
     * @param  string $module 
     * @param  array  $params 
     * @return object
     */
    protected function reopenModule($module, $params = array())
    {
        if (is_null($instance = $this->getModule($module))) {
            $instance = $this->createModule($module, $params);
        } elseif (is_object($instance)) {
            $this->closeModule($module);
            $instance = $this->createModule($module, $params);
        }
        $this->log(E_NOTICE, "Reopen Module {$module}", true, $module);
        return $instance;
    }
    /**
     * close module 
     * @param  string $module 
     * @return void
     */
    protected function closeModule($module)
    {
        $this->triggerOnCloseModule($module);
        $this->unsetModule($module);
    }
    /**
     * trigger onClose
     * @param  string $module 
     * @return void
     */
    protected function triggerOnCloseModule($module)
    {
        $md = $this->getModule($module);
        if (is_object($md) && ($md instanceof CloseModuleInterface)) {
            $this->log(E_NOTICE, "Call ".get_class($md)."->onClose()", true, $module);
            $md->onClose();
        }
    }
    /**
     * unset Module 
     * @param  string $module 
     * @return void
     */
    protected function unsetModule($module) {
        if (isset($this->modules[$module])) {
            unset ($this->modules[$module]);
        }
        $this->log(E_NOTICE, "Close Module", true, $module);
    }
    /**
     * close All Module sequence 
     * @param  array $modules 
     * @return void
     */
    protected function closeAllModule($modules)
    {
        foreach($modules as $module) {
            $this->triggerOnCloseModule($module);
        }
        foreach($modules as $module) {
            $this->unsetModule($module);
        }
    }
    /**
     * return module instance
     * @param  array $module 
     * @return object|false|null  false...already open but no instance / null...open yet
     */
    public function getModule($module, $tryopen = false)
    {
        if (isset($this->modules[$module])) {
            return $this->modules[$module]['instance'];
        }
        if ($tryopen) {
            return $this->openModule($module);
        }
        return null;
    }
    /**
     * create module instance 
     * @param  string $module 
     * @param  array $params 
     * @return object|false
     */
    protected function createModule($module, $params = array())
    {
        $this->modules[$module]['instance']  = false;
        $this->modules[$module]['variables'] = array();
        $this->modules[$module]['objects']   = array();
        if ($module == 'GLOBAL') {
            $this->globalModuleInit();
        }
        $classname = $this->getModuleNamespace($module)."\\Module";
        if (class_exists($classname)) {
            $this->modules[$module]['instance'] = new $classname($params);
            $this->log(E_NOTICE, 'CREATE Module Instance ('.get_class($this->modules[$module]['instance']).")", true, $module);
        } else {
            $this->log(E_NOTICE, 'Not Found Module class. ('.$classname.")", true, $module);
        }
        return $this->modules[$module]['instance'];
    }
    /**
     * global module initialize
     * @todo add basic variable  date. time etc
     * @return void
     */
    protected function globalModuleInit(){
        $this->setVariable('_GET', $_GET ,'GLOBAL');
        $this->setVariable('_POST', $_POST ,'GLOBAL');
    }
    /**
     * refresh module
     * @param  string $module
     * @param  array  $params
     * @return void
     */
    protected function refreshModule($module, $params = array())
    {
        $md = $this->getModule($module);
        if (is_object($md) && ($md instanceof RefreshModuleInterface)) {
            $md->onRefresh($params);
            $this->log(E_NOTICE, 'Module Refresh', true, $module);
        } else {
            $this->log(E_WARNING, 'Not exists refresh method in class Module ', true, $module);
        }
    }
    /**
     * get Modulename by classname
     * @param  string $class 
     * @return string|false;
     */
    public function getModuleNameByClass($class)
    {
        if (is_string($class) && strpos($class, "Module_")==0) {
            $class_array = explode("\\",$class);
            return str_replace("Module_","",$class_array[0]);
        } else {
            return false;
        }
    }
    // pull --------------------------------------------------------------------
    /**
     * call pull return variables array
     * @param  string $pull 
     * @param  string $module 
     * @param  array  $params 
     * @return array
     */
    public function getPull($pull, $module, $params)
    {
        return $this->callGetVariables('Pull', $pull, $module, $params, array());
    }
    // loop --------------------------------------------------------------------
    /**
     * call loop return variable array
     * @param  string $loop 
     * @param  string $module 
     * @param  array  $params 
     * @return array
     */
    public function getLoop($loop, $module, $params)
    {
        return $this->callGetVariables('Loop', $loop, $module, $params, array(array()));
    }
    // call --------------------------------------------------------------------
    /**
     * call pull or loop & return variables.
     * try  1.module method  2.create instance->method  3.callable  4.return default empty
     * @param  string $kind    'pull' / 'loop'
     * @param  string $name    pull name / loop name
     * @param  string $module  module name
     * @param  array  $params  tag attribute params
     * @param  mixed  $default return default
     * @return mixed  array|object  case pull: array() / case loop: array(array())
     */
    protected function callGetVariables($kind, $name, $module, $params, $default)
    {
        $md = $this->getModule($module);
        $methods[] = strtolower($kind).$name;
        $methods[] = strtolower($kind).'_'.strtolower($name);
        // First, search module method
        if (is_object($md)) {
            foreach($methods as $method){
                if (is_callable(array($md, $method))) {
                    $this->log(E_NOTICE, "Call Module_{$module}\\Module->{$method}()", true, $module);
                    return $md->$method($params);
                }
            }
        }
        // Second, search pull/loop class   \Module_module\Methods or Loops\name
        $classname = $this->getModuleNamespace($module)."\\".ucfirst($kind)."s\\".$name;

        if (class_exists($classname)) {
            $rc = new \ReflectionClass($classname);
            foreach($methods as $method) {
                if (is_callable(array($classname, $method))) {
                    $rm = new \ReflectionMethod($classname, $method);
                    if ($rm->isStatic()) {
                        $this->log(E_NOTICE, "{$kind} Call static method. {$classname}::{$method}()", true, $module);
                        return $classname::$method($params);

                    } elseif ($rc->isInstantiable()) {
                            $instance = new $classname($params);
                            $this->log(E_NOTICE, "{$kind} Create instance and call. {$classname}->{$method}()", true, $module);
                            return $instance->$method($params);
                    } else {
                        $this->log(E_WARNING, "{$kind} Not callable method exist. {$classname}::{$method}()", true, $module);
                    }
                    unset($rm);
                }
            }
            if ($rc->isInstantiable() && $rc->hasMethod('__invoke')) {
                $rm = $rc->getMethod('__invoke');
                if ($rm->isPublic()) {
                    $this->log(E_NOTICE, "{$kind} Create invokable instance and call.  {$classname}()", true, $module);
                    $instance = new $classname($params);
                    return $instance($params);
                }
            }
            $this->log(E_ERROR,"{$kind} Not found method. {$classname}::{$methods[0]}() or {$methods[1]}()", true, $module);
            return $default;
        }
        $this->log(E_ERROR,"{$kind} Not Found class {$module} or {$classname}::{$methods[0]} or {$methods[1]}", true, $module);
        return $default;
    }
    /**
     * get Template
     * @param  string $name
     * @param  string $module
     * @return string | false
     */
    public function getTemplate($name, $module = 'GLOBAL')
    {
        $DS = DIRECTORY_SEPARATOR;
        $directories = $this->getConfig('agent_directories');
        $filename = $this->getModuleNamespace($module).$DS."Templates".$DS;
        $filename .= str_replace('_', $DS, $name).$this->getConfig('template_ext');

        foreach($directories as $directory) {
            if (($source = $this->readFile($directory.$filename)) !== false) {
                $this->log(E_NOTICE,"Read Template {$name}:({$filename})", true, $module);
                return $source;
            }
        }

        $this->log(E_ERROR,"Can not load template:".$filename, true, $module);
        return false;
    }
    // buffer ----------------------------------------------------------------------------
    /**
     * create buffer
     * @param  string $str 
     * @return object
     */
    public function createBuffer($name = '__GLOBAL__')
    {
        return $this->buffers[$name] = new Buffer;
    }
    /**
     * buffer
     * @param  string $str 
     * @param  string $name
     * @return string
     */
    public function buffer($str, $name = '__GLOBAL__')
    {
        $this->buffers[$name] .= $str;
        return $str;
    }

    /**
     * get buffer
     * @param  string $name 
     * @return object|null
     */
    public function getBuffer($name = '__GLOBAL__')
    {
        return (isset($this->buffers[$name])) ? $this->buffers[$name] : null ;
    }
    // fetch & parse   --------------------------------------------------------------------
    /**
     * shutdown display   resister_shutdown_function
     * @return type
     */
    public function shutdown()
    {
        $this->display();
    }
    /**
     * out buffer callback
     * @param type $str 
     * @return type
     */
    public function obCallback($str)
    {
        chdir($this->cwd);
        if ($this->debug()) {
            $e = error_get_last();
            if (! is_null($e)) {
                $this->log($e['type'],"{$e['message']} in {$e['file']} ({$e['line']})",false,'PHP');

                if ($e[type]==E_ERROR) {
                    $ob   = $str;
                    $str  = $this->getBuffer();
                    $str .= "<hr>".$ob;
                    $str .= "<hr>".$this->logger->report($this->log_reporting());
                }
            }
        }
        return $str;
    }
    /**
     * dispaly source.  if no source argument, display output buffer
     * @param  string $source 
     * @return void
     */
    public function display($source = null)
    {
        if (is_null($source)) {
            $source = ob_get_clean();
        }
        echo $this->fetch($source);
        if ($this->debug()) {
            echo $this->logger->report($this->log_reporting());
        }
    }
    /**
     * Display from file 
     * @param  string $filename 
     * @return bool  true|false
     */
    public function fileDisplay($filename)
    {
        if (($source = $this->readFile($filename)) !== false) {
            $this->display($source);
            return true;
        }
        return false;
    }
    /**
     * Fetch from file 
     * @param  string $filename 
     * @return string|false
     */
    public function fileFetch($filename, ParseResource $resource = null)
    {
        if (($source = $this->readFile($filename)) !== false) {
            return $this->fetch($source);
        } else {
            return false;
        }
    }
    /**
     * read file
     * @param  string $filename 
     * @return string
     */
    public function readFile($filename)
    {
        if (is_readable($filename) &&  ($source = file_get_contents($filename)) !== false) {
            return $source;
        }
        $this->log(E_WARNING,'readFile() not found filename:'.$filename, true, 'AGENT');
        return false;
    }
    /**
     * fetch tag parse . Retrieve nested 'agent tag' by recursive call.
     * @todo Buffer, attribute store restore
     * @param  string $source
     * @param  object $resource
     * @return string
     */
    public function fetch($source, ParseResource $resource = null)
    {
        // pre-process  global fetch
        $flagGlobal = false;
        if (is_null($resource)) {
            $this->line = 0;
            $buffer = $this->createBuffer();
            $resource = new ParseResource($buffer);
            $this->openModule('GLOBAL');
            $flagGlobal = true;
            $this->line = 1;
            $this->log(E_NOTICE,'---------- PRE PROCESS [up to here]----------', true, $resource->module);
        }

        $sourceLine = $this->line + substr_count($source, "\n");

        $tag = $this->getConfig("agent_tag"); // default ag
        $pattern = "/<".$tag."\s+((?:\"[^\"]*\"|'[^']*'|[^'\">])*?)\s*>((?:(?>[^<]+)|<(?!(".$tag."|\/".$tag.")(>|\s))|(?R))*)<\/".$tag.">/is";
        // <tag> search
        while (preg_match($pattern, $source, $matches, PREG_OFFSET_CAPTURE)) {
            $match = $matches[0][0]; // <tag></tag>
            $pos   = $matches[0][1]; // '<'' start posistion
            $attr  = $matches[1][0]; // <tag $attr></tag>
            $inTag = $matches[2][0]; // <tag>$inTag</tag>
            $len   = strlen($match);

            $before = substr($source, 0, $pos);
            $source = substr($source, $pos + $len);

            // line
            $beforeLine = $this->line + substr_count($before, "\n");
            $matchLine  = $beforeLine + substr_count($match, "\n");

            // trimming
            $trimLineTag   = 0;
            $trimLineSorce = 0;
            if (preg_match('/(\n|^)[ \t]*$/', $before) && preg_match('/^[ \t]*\r?\n/', $inTag)) {
                $before = rtrim($before, ' \t');
                $inTag  = preg_replace('/^[ \t]*\r?\n/', '', $inTag, 1, $trimLineTag);
            }
            if (preg_match('/\n[ \t]*$/', $inTag) && preg_match('/^[ \t]*\r?\n/', $source)){
                $inTag  = rtrim($inTag, ' \t');
                $source = preg_replace('/^[ \t]*\r?\n/', '', $source, 1, $trimLineSorce);
            }

            // varFetch before tag
            if ($flagGlobal) {
                $resource->buffer($before);
            } else {
                $resource->varFetch($before);
            }
            unset($before);
            $this->line = $beforeLine;

            // attribute parse
            $attrs = new Attribute($attr, $resource);

            // debug
            if (isset($attrs->reserved['Debug'])) {
                $this->debug(Utility::boolStr($attrs->reserved['Debug']));
            }
            // header
            if (isset($attrs->reserved['Header'])) {
                $header = HttpHeader::header($attrs->reserved['Header']);
                $this->log(E_NOTICE,"header({$header})",true,'AGENT');
                header($header);
            }
            // store
            if (isset($attrs->reserved['Store'])) {
                $store = $attrs->reserved['Store'];
                $buffer = $this->createBuffer($store);
                $this->log(E_NOTICE,"Store: {$store}", true, $resource->module);
            } else {
                $buffer = $resource->buffer;
            }
            // resource 
            $inResource = new ParseResource($buffer);
            //module control
            if (isset($attrs->reserved['Module'])) {
                $module = $inResource->module = $attrs->reserved['Module'];
                $this->openModule($module, $attrs->params);
            } else {
                $module = $inResource->module = $resource->module;
            }
            // reopen 
            if (Utility::boolStr($attrs->reserved['Reopen'])) {
                $this->reopenModule($module, $attrs->params);
            }
            // close
            $forceClose = Utility::boolStr($attrs->reserved['Close'], false);
            // refresh
            if (Utility::boolStr($attrs->reserved['Refresh'], false)) {
                $this->refreshModule($module, $attrs->params);
            }
            // pull vars
            if (isset($attrs->reserved['Pull'])) {
                $inResource->pullVars = $this->getPull($attrs->reserved['Pull'], $module, $attrs->params);
            } else {
                $inResource->pullVars = $resource->pullVars;
            }
            // appends vars
            foreach ($attrs->appends as $key => $value) {
                $inResource->pullVars[$key] = $value;
            }
            // loop vars
            $inLoopVarsList = (isset($attrs->reserved['Loop']))
                            ? $this->getLoop($attrs->reserved['Loop'], $module, $attrs->params)
                            : array('_NOLOOP_'=>$resource->loopVars);
            // template
            if (isset($attrs->reserved['Template'])) {
                if ( ($content = $this->getTemplate($attrs->reserved['Template'], $module))!==false) {
                    $inTag = $content; // replace inTag to template
                }
            }
            // read
            if (isset($attrs->reserved['Read'])) {
                if ( ($content = $this->readFile($attrs->reserved['Read'], $module))!==false) {
                    $inTag = $content; // replace inTag to file content.
                }
            }
            // restore
            if (isset($attrs->reserved['Restore'])) {
                $restore = $attrs->reserved['Restore'];
                if (! is_null($buffer = $this->getBuffer($restore))) {
                    $inTag = (string) $buffer;
                    $attrs->reserved['Parse'] = false;
                    $this->log(E_NOTICE,"Restore: {$restore}", true, $resource->module);
                } else {
                    $this->log(E_WARNING,"Restore: {$restore} Not Found Buffer", true, $resource->module);
                }
            }
            // trim
            if (Utility::boolStr($attrs->reserved['Trim'], false)) {
                preg_match('/^\s*/', $inTag, $trimMatch);
                $trimLineTag += substr_count($trimMatch[0],"\n");
                $inTag = preg_replace('/(^\s*|\s*$)/', '', $inTag);
            }
            // check
            if (Utility::boolStr($attrs->reserved['Check'], false)) {
                $this->checkResourceLog($inResource, $inLoopVarsList);
            }

            // output parse switch
            if (Utility::boolStr($attrs->reserved['Parse'], true) ) {
                // parse = yes
                foreach($inLoopVarsList as $key => $inResource->loopVars) {
                    $this->line = $beforeLine + $trimLineTag; 
                    $inResource->loopkey = ($key !== '_NOLOOP_') ? $key : '';
                    // recursive fetch inside
                    $this->fetch($inTag, $inResource);
                }
            } else {
                // parse = no
                $inResource->buffer($inTag);
                if (! isset($attrs->reserved['Restore'])) {
                    $this->log(E_NOTICE,'Parse: No', true, $resource->module);
                }
            }
            $this->line = $matchLine;

            // close module
            if ($forceClose) {
                if ($module !== 'GLOBAL' ) {
                    $this->closeModule($module);
                } else {
                    $this->log(E_WARNING, "GLOBAL module can not be forced close", true, $module);
                }
            }
            // line incriment source trim 
            $this->line += $trimLineSorce;
        } // end of while serch <tag>

        // remaining non Tag-match string 
        if ($flagGlobal) {
            $resource->buffer($source);
        } else {
            $resource->varFetch($source);
        }
        $this->line = $sourceLine;

        // post-process global fetch
        if ($flagGlobal) {
            $this->log(E_NOTICE,'---------- POST PROCESS [from here]----------',true,$resource->module);
            // all module close sequence
            $modules = array_reverse($this->modules);
            $this->closeAllModule(array_keys($modules));
            $this->line = 0;
            return $resource->buffer;
        }
    }
    // Error for debug -------------------------------------------
    /**
     * return log report
     * @return mixed string|fale
     */
    public function getLogReport()
    {
        if ($this->debug()) {
            return $this->logger->report($this->log_reporting());
        }
        return false;
    }
    /**
     * log
     * @param  integer|string $level
     * @param  string $message
     * @param  string $module 
     * @return void
     */
    public function log($level, $message, $escape = true, $module = "", $callerback = null)
    {
        if ($this->debug()) {
            if (! is_null($callerback)) {
                $br = ($escape) ? "\n" : '<br />';
                $caller = Utility::getCaller((int) $callerback + 1);
                if ($caller['class'] !== __CLASS__) {
                    $message .= $br.'Caller: '.$caller['classmethod']."() ".$caller['fileline'];
                }
            }
            $this->logger->log($level, $message, $escape, $module);
        }
    }
    public function checkResourceLog(ParseResource $resource, $inLoopVarsList)
    {
        if ($this->debug()) {
            $module = $resource->module;
            $check  = "<ul>";
            $check .= " <li>Pull variables\n".ExpandVariable::expand($resource->pullVars)."</li>";
            $check .= " <li>Loop variables\n".ExpandVariable::expand($inLoopVarsList)."</li>";
            $check .= " <li>{$module} Module variables\n".ExpandVariable::expand($this->getVariable(null, $module))."</li>";
            if ($module !== 'GLOBAL') {
                $check .= " <li>GLOBAL Module variables\n".ExpandVariable::expand($this->getVariable(null, 'GLOBAL'))."</li>";
            }
            $check .= "</ul>";
            $this->log(E_DEPRECATED, $check, false, $module);
        }
    }

} // end of Agent class 
