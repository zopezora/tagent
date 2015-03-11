<?php
/**
 * Agent, part of Tagent
 * tag parser, module control, Object locator
 * @package Tagent
 */
namespace Tagent;

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
    const CHARSET          = 'utf-8';
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
     * @var string
     */
    protected $tagPattern = '';
    /**
     * @var object   Object ModuleLoader
     */
    protected $loader = null;
    /**
     * @var array    ['modulename']['instance']/['objects']/['variables'] 
     */
    protected $modules = array();
    /**
     * @var object filter manager object
     */
    public $filterManager = null;
    /**
     * @var object HttpHeaderManager object
     */
    public $httpHeaderManager = null;
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
        require_once(__DIR__.DIRECTORY_SEPARATOR.'Attribute.php');
        require_once(__DIR__.DIRECTORY_SEPARATOR.'Buffer.php');
        require_once(__DIR__.DIRECTORY_SEPARATOR.'Filter.php');
        require_once(__DIR__.DIRECTORY_SEPARATOR.'FilterManager.php');
        require_once(__DIR__.DIRECTORY_SEPARATOR.'HttpHeader.php');
        require_once(__DIR__.DIRECTORY_SEPARATOR.'HttpHeaderManager.php');
        require_once(__DIR__.DIRECTORY_SEPARATOR.'ModuleLoader.php');
        require_once(__DIR__.DIRECTORY_SEPARATOR.'ParseResource.php');
        require_once(__DIR__.DIRECTORY_SEPARATOR.'Utility.php');

        require_once(__DIR__.DIRECTORY_SEPARATOR.'AbstractModule.php');
        require_once(__DIR__.DIRECTORY_SEPARATOR.'CloseModuleInterface.php');
        require_once(__DIR__.DIRECTORY_SEPARATOR.'FactoryInterface.php');
        require_once(__DIR__.DIRECTORY_SEPARATOR.'RefreshModuleInterface.php');

        // set config default 
        $this->configs = array (
            "charset"           => self::CHARSET,
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
        // filter manager
        $this->filterManager = new FilterManager();
        // HeaderManager
        $this->httpHeaderManager = new HttpHeaderManager($this->configs['charset']);
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
            return $this->configs['debug'];
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
    /**
     * db return PDO handle
     * configs[pdo][name]   [dsn][username][password][options]
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
                $this->db[$name] = new \PDO($dsn, $user, $password, $options);
            } catch (\PDOException $e) {
                $this->log(E_ERROR,$e->getMessage(), true, 'AGENT_DB', 0);
                return $this->db[$name] = false;
            }
            $this->log(E_NOTICE,"Connect DB '{$name}': dsn={$dsn}", true, 'AGENT_DB', 0);
            return $this->db[$name];
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
        $methods[] = strtolower($kind).str_replace('_', '', $name);
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
        if ($this->configs['debug']) {
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
        if ($this->configs['debug']) {
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
     * start fetch 
     * @param string $source 
     * @return string
     */
    public function fetch($source){
        $this->line = 0;
        $resource = new ParseResource();
        $this->openModule('GLOBAL');
        $this->line = 1;
        $this->log(E_NOTICE,'---------- PRE PROCESS [up to here]----------', true, $resource->module);

        $tag = $this->getConfig("agent_tag"); // default ag
        $this->tagPattern = "/<".$tag."\s*((?:[^'\">]|\"(?:\\\\\"|[^\"])*\"|'(?:\\\\'|[^'])*')*?)\s*>((?:(?>[^<]+)|<(?!(".$tag."|\/".$tag.")(>|\s))|(?R))*)<\/".$tag.">/is";
        $this->recursiveFetch($source, $resource, true);

    // post-process global fetch
        $this->log(E_NOTICE,'---------- POST PROCESS [from here]----------', true, $resource->module);
        // all module close sequence
        $modules = array_reverse($this->modules);
        $this->closeAllModule(array_keys($modules));
        $this->line = 0;
        return $resource->buffer;
    }
    /**
     * recursive fetch tag parse . Retrieve nested 'agent tag' by recursive call.
     * @param  string $source
     * @param  object $resource
     * @return mixed string|void
     */
    protected function recursiveFetch($source, ParseResource $resource, $first = false)
    {

        $sourceLine = $this->line + substr_count($source, "\n");
        $outputMethod = ($first) ? 'buffer' : 'varFetch';

        // <tag> search
        while (preg_match($this->tagPattern, $source, $matches, PREG_OFFSET_CAPTURE)) {
            // $matches[0][0]   <tag> to </tag>
            // $matches[0][1]   '<'' start posistion
            // $matches[1][0]   tag attribute
            // $matches[2][0]   inTag

            // attribute parse
            $attrs = new Attribute($matches[1][0], $resource); // $matches[1][0] <tag $attr></tag>

            $before = substr($source, 0, $matches[0][1]);
            $len   = strlen($matches[0][0]); // mattch <tag> to </tag>
            $source = substr($source, $matches[0][1] + $len);

            // line
            $beforeLine = $this->line + substr_count($before, "\n");
            $matchLine  = $beforeLine + substr_count($matches[0][0], "\n"); // match <tag> to </tag>

            // attribute parse
            $attrs = new Attribute($matches[1][0], $resource);
            // resource
            $inResource = new ParseResource($resource);
            $inResource->inTag = $matches[2][0];

            // trimming
            $trimLineSorce = 0;

            if (preg_match('/^[ \t]*\r?\n/', $inResource->inTag)) {
                $last = strrpos($before, "\n");
                $search = ($last===false) ? $before : substr($before, $last+1);
                if (preg_match('/^[ \t]*$/', $search)) {
                    $before = rtrim($before, " \t");
                    $inResource->inTag = preg_replace('/^[ \t]*\r?\n/', '' , $inResource->inTag, 1, $inResource->trimLineTag);
                }
            }
            if (preg_match('/^[ \t]*\r?\n/', $source)) {
                $last = strrpos($inResource->inTag, "\n");
                $search = ($last===false) ? $inResource->inTag : substr($inResource->inTag, $last+1);
                if (preg_match('/^[ \t]*$/', $search)) {
                    $inResource->inTag  = rtrim($inResource->inTag, " \t");
                    $source = preg_replace('/^[ \t]*\r?\n/', '', $source, 1, $trimLineSorce);
                }
            }

            // varFetch before tag
            $inResource->$outputMethod($before);
            $this->line = $beforeLine;

            // attribute process
            foreach ($attrs->reserved as $attr) {
                $method = 'attr'.$attr[0];
                $this->$method($attr[1], $attrs, $inResource);
            }

            // appends vars
            foreach ($attrs->appends as $key => $value) {
                $inResource->pullVars[$key] = $value;
            }
            // output parse switch
            if ($inResource->parse) {
                // parse = yes
                foreach($inResource->inLoopVarsList as $key => $inResource->loopVars) {
                    $this->line = $beforeLine + $inResource->trimLineTag; 
                    $inResource->loopkey = ($key !== '_NOLOOP_') ? $key : '';
                    // recursive fetch inside
                    $this->recursiveFetch($inResource->inTag, $inResource);
                }
            } else {
                // parse = no
                $inResource->buffer->buffer($inResource->inTag);
                $this->log(E_NOTICE,'Parse: No', true, $resource->module);
            }
            $this->line = $matchLine;

            // close module
            if ($inResource->forceClose) {
                if ($inResource->module !== 'GLOBAL' ) {
                    $this->closeModule($inResource->module);
                } else {
                    $this->log(E_WARNING, "GLOBAL module can not be forced close", true, $inResource->module);
                }
            }
            // line incriment source trim 
            $this->line += $trimLineSorce;
        } // end of while serch <tag>

        // remaining non Tag-match string 
        $resource->$outputMethod($source);
        $this->line = $sourceLine;
    }
    /**
     * attribute 'Debug' process
     * @param object $attrs
     * @param object $resource 
     * @return void
     */
    protected function attrDebug($value, $attrs, $inResource)
    {
        $this->debug(Utility::boolStr($value, false));
    }
    /**
     * attribute 'Header' process
     * @param object $attrs
     * @param object $resource 
     * @return void
     */
    protected function attrHeader($value, $attrs, $inResource)
    {
        $header = $this->httpHeaderManager->header($value);
        $this->log(E_NOTICE,"header({$header})",true,'AGENT');
        header($header);
    }
    /**
     * attribute 'Store' process
     * @param object $attrs
     * @param object $resource 
     * @return void
     */
    protected function attrStore($value, $attrs, $inResource)
    {
        $inResource->setBuffer($this->createBuffer($value));
        $this->log(E_NOTICE,"Store: {$value}", true, 'AGENT');
    }
    /**
     * attribute 'Module' process
     * @param object $attrs
     * @param object $resource 
     * @return void
     */
    protected function attrModule($value, $attrs, $inResource)
    {
        $module = $inResource->module = $value;
        $this->openModule($module, $attrs->params);
    }
    /**
     * attribute 'Reopen' process
     * @param object $attrs
     * @param object $resource 
     * @return void
     */
    protected function attrReopen($value, $attrs, $inResource)
    {
        if (Utility::boolStr($value, false)) {
            $this->reopenModule($inResource->module, $attrs->params);
        }
    }
    /**
     * attribute 'Close' process
     * @param object $attrs
     * @param object $resource 
     * @return void
     */
    public function attrClose($value, $attrs, $inResource)
    {
        $inResource->forceClose = Utility::boolStr($value, false);
    }
    /**
     * attribute 'Refresh' process
     * @param object $attrs
     * @param object $resource 
     * @return void
     */
    protected function attrRefresh($value, $attrs, $inResource)
    {
        if (Utility::boolStr($value)) {
            $this->refreshModule($inResource->module, $attrs->params);
        }
    }
    /**
     * attribute 'Pull' process
     * @param object $attrs
     * @param object $resource 
     * @return void
     */
    protected function attrPull($value, $attrs, $inResource)
    {
        $inResource->pullVars = $this->getPull($value, $inResource->module, $attrs->params);
    }
    /**
     * attribute 'Loop' process
     * @param object $attrs
     * @param object $resource 
     * @return void
     */
    protected function attrLoop($value, $attrs, $inResource)
    {
        $inResource->inLoopVarsList = $this->getLoop($value, $inResource->module, $attrs->params);
    }
    /**
     * attribute 'Template' process
     * @param object $attrs
     * @param object $resource 
     * @return void
     */
    protected function attrTemplate($value, $attrs, $inResource)
    {
        if ( ($content = $this->getTemplate($value, $inResource->module))!==false) {
            $inResource->inTag = $content; // replace inTag to template
        }
    }
    /**
     * attribute 'Read' process
     * @param object $attrs
     * @param object $resource 
     * @return void
     */
    protected function attrRead($value, $attrs, $inResource)
    {
        if ( ($content = $this->readFile($value, $inResource->module))!==false) {
            $inResource->inTag = $content; // replace inTag to file content.
        }
    }
    /**
     * attribute 'Restore' process
     * @param object $attrs
     * @param object $resource 
     * @return void
     */
    protected function attrRestore($value, $attrs, $inResource)
    {
        if (! is_null($buffer = $this->getBuffer($value))) {
            $inResource->inTag = (string) $buffer;
            $this->log(E_NOTICE,"Restore: {$value}", true, 'AGENT');
        } else {
            $this->log(E_WARNING,"Restore: {$value} Not Found Buffer", true, 'AGENT');
        }
    }
    /**
     * attribute 'Trim' process
     * @param object $attrs
     * @param object $resource 
     * @return void
     */
    protected function attrTrim($value, $attrs, $inResource)
    {
        if (Utility::boolStr($value, false)) {
            preg_match('/^\s*/', $inResource->inTag, $trimMatch);
            $inResource->trimLineTag += substr_count($trimMatch[0],"\n");
            $inResource->inTag = preg_replace('/(^\s*|\s*$)/', '', $inResource->inTag);
        }
    }
    /**
     * attribute 'Check' process
     * @param object $attrs
     * @param object $resource 
     * @return void
     */
    public function attrCheck($value, $attrs, $inResource)
    {
        if (Utility::boolStr($value, false)) {
            $this->checkResourceLog($inResource);
        }
    }
    /**
     * attribute 'Parse' process
     * @param object $attrs
     * @param object $resource 
     * @return void
     */
    public function attrParse($value, $attrs, $inResource)
    {
        $inResource->parse = Utility::boolStr($value, true);
    }
    // Error for debug -------------------------------------------
    /**
     * return log report
     * @return mixed string|fale
     */
    public function getLogReport()
    {
        if ($this->configs['debug']) {
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
        if ($this->configs['debug']) {
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
    public function checkResourceLog(ParseResource $resource)
    {
        if ($this->configs['debug']) {
            $module = $resource->module;
            $check  = "<ul>";
            $check .= " <li>Pull variables\n".ExpandVariable::expand($resource->pullVars)."</li>";
            $check .= " <li>Loop variables\n".ExpandVariable::expand($resource->inLoopVarsList)."</li>";
            $check .= " <li>{$module} Module variables\n".ExpandVariable::expand($this->getVariable(null, $module))."</li>";
            if ($module !== 'GLOBAL') {
                $check .= " <li>GLOBAL Module variables\n".ExpandVariable::expand($this->getVariable(null, 'GLOBAL'))."</li>";
            }
            $check .= "</ul>";
            $this->log(E_DEPRECATED, $check, false, $module);
        }
    }

} // end of Agent class 
