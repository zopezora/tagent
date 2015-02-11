<?php
/**
 * Agent, part of Tagent
 * tag parser, module control, Object locator
 * @package Tagent
 */
namespace Tagent;

use Tagent\AbstractModule;
use Tagent\FactoryInterface;

class Agent {

    // config default
    const AGENT_TAG        = "ag";
    const AGENT_DIRECTORY  = "ag/";
    const DEBUG            = false;
    const SHUTDOWN_DISPLAY = false;
    const LINE_OFFSET      = 0;
    const TEMPLATE_EXT     = ".tpl";
    const LOG_REPORTING    = E_ALL;

    // const pattern
    const RESERVED_ATTRS  = 'module|method|loop|parse|close|refresh|newmodule|template|check';
    const OUTPUT_FORMATS  = 'h|r|u|j|html|raw|url|json';
    const VARIABLE_SCOPES = 'm|l|g|module|loop|global';
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
     * @var string output buffer
     */
    protected $buffer = '';
    /**
     * @var object  logger object
     */
    protected $logger = null;
    /**
     * @var integer  line counter
     */
    protected $line = 0;
    /**
     * @var integer  loglevel
     */
    protected $loglevel = array(
                                E_ERROR   => 'ERROR',   // 1
                                E_WARNING => 'WARNING', // 2
                                E_PARSE   => 'PARSE',   // 4
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
     * configure , out buffer start
     * @param array $config 
     * @param bool  $bufferstart 
     * @access protected
     */
    protected function __construct(array $config = array())
    {
        // set config default 
        $this->configs = array (
            "agent_tag"        => self::AGENT_TAG,
            "agent_directory"  => self::AGENT_DIRECTORY,
            "debug"            => self::DEBUG,
            "shutdown_display" => self::SHUTDOWN_DISPLAY,
            "line_offset"      => self::LINE_OFFSET,
            "template_ext"     => self::TEMPLATE_EXT,
            "log_reporting"    => self::LOG_REPORTING,
        );
        // Config override
        $this->configs = $this->arrayOverride( $this->configs, $config );
        // Logger
        if ($this->debug()) {
            $this->logger = new Logger;
        }
        // Module Autoloader
        $this->loader = ModuleLoader::init($this->configs['agent_directory']);
        // currnt directory for outbuffer callback
        $this->cwd = getcwd();
        // shutdown display
        $this->configs['shutdown_display'] = $this->boolStr($this->configs['shutdown_display'], self::SHUTDOWN_DISPLAY);
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
    public function debug()
    {
        return $this->boolStr($this->configs['debug'], false);
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
     * @param string $key 
     * @return mixed|null
     */
    public function getVariable($key = null, $module = 'GLOBAL')
    {
        if (! isset($key)) {
            return (isset($this->modules[$module]['variables'])) ? $this->modules[$module]['variables'] : null;
        }
        return (isset($this->modules[$module]['variables'][$key])) ? $this->modules[$module]['variables'][$key] : null;
    }
    /**
     * set/unset variables  always override
     * @param string $key 
     * @param mixed $var 
     * @return void
     */
    public function setVariable($key, $value, $module = 'GLOBAL')
    {
        if (! isset($key)){
            return ;
        }
        if (! is_null($this->getModule($module))) {
            if (is_null($value) && isset($this->modules[$module]['variables'][$key])) {
                unset ($this->modules[$module]['variables'][$key]);
                return;
            }
            $this->modules[$module]['variables'][$key] = $value;
        } else {
            $this->log(E_WARNING, 'setVariable key='.$key.' module='.$module.' not open yet');
        }
    }
    /**
     * override set Variables by array  
     * @param  array  $array 
     * @param  string $module 
     * @return void
     */
    public function setVariablesByArray(array $array, $module = 'GLOBAL')
    {
        if (! is_null($this->getModule($module))) {
            $this->modules[$module]['variables'] = $array;
        } else {
            $this->log(E_WARNING, 'setVariableByArray module='.$module.' is not open yet');
        }
    }
    // Object Locator --------------------------------------------------------------------
    /**
     * get object
     * @name   string $name 
     * @param  string $module 
     * @return object
     */
    public function get($name, $module = 'GLOBAL')
    {
        if (! isset($name) || ! is_string($name)) {
            $this->log(E_WARNING,"Object Locator:get('{$name}','{$module}') Unvalid name" );
            return null;
        }
        if (isset($this->modules[$module])) {
            if (isset($this->modules[$module]['objects'][$name])) {
                $object = $this->modules[$module]['objects'][$name];
                if ($object instanceof FactoryInterface) {
                    $this->modules[$module]['objects'][$name] = $object->factory();
                    $this->log(E_NOTICE,"Object Locator:get('{$name}','{$module}') call ".get_class($object)."->factory().");
                    return $this->modules[$module]['objects'][$name];
                }
                return $object;
            } else {
                $this->log(E_WARNING,"Object Locator:get('{$name}','{$module}') Not Found.");
            }
        } else {
            $this->log(E_WARNING,"Object Locator:get('{$name}','{$module}') module is not open yet.");
        }
        return null;
    }
    /**
     * set object
     * @param  string $name 
     * @param  object $object   if null , unset Object
     * @param  string $module
     * @return void
     */
    public function set($name, $object, $module = 'GLOBAL')
    {
        $objectname = (is_object($object)) ? get_class($object) : "";
        if (! isset($name) || ! is_string($name)) {
            $this->log(E_WARNING, "Object Locator: set('{$name}','{$objectname}',{$module}' Unvalid name");
            return;
        }
        if (isset($this->modules[$module])) {
            if (is_null($object) && isset($this->modules[$module]['objects'][$name])) { 
                unset ($this->modules[$module]['objects'][$name]);
            } else {
                $this->modules[$module]['objects'][$name] = $object;
            }
        } else {
            $this->log(E_WARNING, "Object Locator: set('{$name}','{$objectname}',{$module}' is not open yet");
        }
    }
    /**
     * has object
     * @param  string $name 
     * @param  string $module 
     * @return bool   true|false
     */
    public function has($name, $module = 'GLOBAL')
    {
        if (! isset($name) || ! is_string($name) ) {
            $this->log(E_WARNING,"Object Locator:has('{$name}','{$module}') Unvalid name" );
            return false;
        }
        if (isset($this->modules[$module])) {
            $this->log(E_WARNING,"Object Locator:has('{$name}','{$module}') module is not open yet.");
            return fale;
        }
        return (isset($this->modules[$module]['objects'][$key])) ? true : false;
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
            $this->log(E_NOTICE, "Open Module", true, $module);
            $instance = $this->createModule($module, $params);
        }
        return $instance;
    }
    /**
     * new open module
     * @param  string $module 
     * @param  array  $params 
     * @return object
     */
    protected function newOpenModule($module, $params = array())
    {
        if (is_null($instance = $this->getModule($module))) {
            $instance = $this->createModule($module, $params);
        } elseif (is_object($instance)) {
            $this->closeModule($module);
            $instance = $this->createModule($module, $params);
        }
        $this->log(E_NOTICE, "new Open Module (".$module.")", true, $module);
        return $instance;
    }
    /**
     * close module 
     * @param  string $module 
     * @return void
     */
    protected function closeModule($module)
    {
        if (! is_null($instance = $this->getModule($module))) {
            if (is_object($instance) && method_exists($instance,'onClose')) {
                $instance->onClose($this);
            }
        }
        if (isset($this->modules[$module])) {
            unset ($this->modules[$module]);
        }
        $this->log(E_NOTICE, "Close Module", true, $module);
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
     * @return type
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
        if (is_object($md) && method_exists($md, 'refresh')) {
            $md->refresh($params);
            $this->log(E_NOTICE, 'Module Refresh', true, $module);
        } else {
            $this->log(E_WARNING, 'Not exists refresh method in module method', true, $module);
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
    // method --------------------------------------------------------------------
    /**
     * call method return variables array
     * @param  string $method 
     * @param  string $module 
     * @param  array  $params 
     * @return array
     */
    public function getMethod($method, $module, $params)
    {
        return $this->callGetVariables('method', $method, $module, $params, array());
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
        return $this->callGetVariables('loop', $loop, $module, $params, array(array()));
    }
    // call --------------------------------------------------------------------
    /**
     * call method or loop & return variables.
     * try  1.module method  2.create instance->method  3.callable  4.return default empty
     * @param  string $kind    'method' / 'loop'
     * @param  string $name    method name / loop name
     * @param  string $module  module name
     * @param  array  $params  tag attribute params
     * @param  mixed  $default return default
     * @return array|object    case method: array() / case loop: array(array())
     */
    protected function callGetVariables($kind, $name, $module, $params, $default)
    {
        // first, search module method
        $md = $this->getModule($module);
        $methodname = $kind."_".$name;
        if (is_object($md) && method_exists($md, $methodname)) {
            $this->log(E_NOTICE, 'Call Module_'.$module.'->'.$methodname, true, $module);
            return $md->$methodname($params);
        }
        // second, search method class   \Module_module\Method or Loops\name
        $classname = $this->getModuleNamespace($module)."\\".ucfirst($kind)."s\\".$name;
        if (class_exists($classname)) {
            $instance = new $classname($params);
            if (method_exists($instance, $methodname)) {
                $this->log(E_NOTICE, 'Call '.$classname.'->'.$methodname, true, $module);
                return $instance->$methodname($params);
            }
            if (is_callable($instance)) {
                $this->log(E_NOTICE, 'Callable '.$kind.' '.$classname.'()', true, $module);
                return $instance($params);
            }
            $this->log(E_WARNING,$king.': '.$classname.' Not found '.$methodname.',and Not Callable.', true, $module);
            return $default;
        }
        $this->log(E_WARNING,'Not Found call ('.$module.'->'.$methodname.')', true, $module);
        return $default;
    }
    /**
     * get Template
     * @param  string $name
     * @param  string $module
     * @return string | false
     */
    public function getTemplate($name, $module = 'GLOBAL') {
        $DS = DIRECTORY_SEPARATOR;
        $filename = $this->getConfig('agent_directory');
        $filename .= $this->getModuleNamespace($module).$DS."Templates".$DS;
        $filename .= str_replace('_', $DS, $name).$this->getConfig('template_ext');
        if (($source = $this->readFile($filename)) !== false) {
            $this->log(E_NOTICE,"Read Template {$name}:({$filename})", true, $module);
            return $source;
        } else {
            $this->log(E_ERROR,"Can not load template:".$filename, true, $module);
        }
        return false;
    }
    // fetch & parse   --------------------------------------------------------------------
    /**
     * shutdown display   resister_shutdown_function
     * @return type
     */
    public function shutdown() {
        $this->display();
    }
    /**
     * out buffer callback
     * @param type $str 
     * @return type
     */
    public function obCallback($str) {
        chdir($this->cwd);
        if ($this->debug()) {
            $e = error_get_last();
            if (! is_null($e)) {
                $this->log($e['type'],"{$e['message']} in {$e['file']} ({$e['line']})",false,'PHP');

                $ob   = $str;
                $str  = $this->buffer;
                $str .= "<hr>".$this->logger->report($this->log_reporting());
                $str .= "<hr>".$ob;
            }
        }
        return $str;
    }


    /**
     * display output buffer   
     * @return void
     */
    protected function bufferDisplay()
    {
        if (is_null($this->buffercallback)){
            $source = ob_get_contents();
            ob_clean();
            $this->display($source);
        } else {
            $source = ob_get_contents();
            ob_clean();
            $this->display($source);

//            ob_flush();
        }
    }
    /**
     * dispaly source.  if no source argument, display output buffer
     * @param  string $source 
     * @return void
     */
    public function display($source = null)
    {
        if (is_null($source)) {
            $this->bufferDisplay();
        } else {
            echo $this->fetch($source);
            if ($this->debug()) {
                echo $this->logger->report($this->log_reporting());
            }
        }
    }
    /**
     * Display from file 
     * @param  string $filename 
     * @return bool  true|false
     */
    public function fileDisplay($filename)
    {
        if ( $source = $this->readFile($filename) !== false ) {
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
        if ($source = $this->readFile($filename) !== false) {
            return $this->fetch($source);
        } else {
            return false;
        }
    }
    public function readFile($filename)
    {
        if (is_readable($filename) &&  ($source = file_get_contents($filename)) !== false) {
            return $source;
        }
        return false;
    }



    public function buffer($str) {
        $this->buffer .= $str;
        return $str;
echo "buffer";
    }

    /**
     * fetch tag parse . Retrieve nested 'agent tag' by recursive call.
     * @todo parameter object (module,methodVars,loopVars,loopkey,line)
     * @param  string  $source
     * @param  object $resource
     * @return string
     */
    public function fetch($source, ParseResource $resource = null)
    {
        // pre-process  global fetch
        $flagGlobal = false;
        if (is_null($resource)) {
            $this->line = 0;
            $resource = new ParseResource;
            $this->openModule('GLOBAL');
            $flagGlobal = true;
            $this->line = $resource->line = 1;
            $this->buffer = '';
        }
        $tag = $this->getConfig("agent_tag"); // default ag
        $pattern = "/<".$tag."\s*((?:\"[^\"]*\"|'[^']*'|[^'\">])*?)\s*>((?:(?>[^<]+)|<(?!(".$tag."|\/".$tag.")(>|\s))|(?R))*)<\/".$tag.">/is";
        // output buffer
        $output = "";
        // <tag> search
        while (preg_match($pattern, $source, $matches, PREG_OFFSET_CAPTURE)) {
            $match = $matches[0][0]; // <tag></tag>
            $pos   = $matches[0][1]; // '<'' start posistion
            $attr  = $matches[1][0]; // <tag $attr></tag>
            $inTag = $matches[2][0]; // <tag>$inTag</tag>
            $len   = strlen($match);

            // before Tag
            $output .= $this->varFetch(substr($source, 0, $pos), $resource);

            $this->line = ($resource->line += substr_count(substr($source, 0, $pos), "\n"));

            // attrs ['params'] / ['reserved'] / ['appends']
            $attrs = $this->attributeParse($attr, $resource);
            $reserved = $attrs['reserved'];

            // parse switch parse= on/off yes/no y/n
            if ( ! $this->boolStr($reserved['parse'], true) ) {
                // parse = no
                $output .= $inTag;
                $this->log(E_NOTICE,'Parse: No', true, $module);
            } else {
                // parse = yes
                $inResource = new ParseResource($resource->line);
                $params = $attrs['params'];
                //module control
                if (isset($reserved["module"])) {
                    $module = $inResource->module = $reserved["module"];
                    $flagModule  = $this->openModule($module, $params);
                } else {
                    $module = $inResource->module = $resource->module;
                    $flagModule  = false;
                }
                // close
                $forceClose = $this->boolStr($reserved['close'], false);
                // refresh
                if ($this->boolStr($reserved['refresh'], false)) {
                    $this->refreshModule($module, $params);
                }
                // method vars
                if (isset($reserved["method"])) {
                    $inResource->methodVars = $this->getMethod($reserved["method"], $module, $params);
                } else {
                    $inResource->methodVars = $resource->methodVars;
                }
                // appends vars
                foreach ($attrs['appends'] as $key => $value) {
                    $inResource->methodVars[$key] = $value;
                }
                // loop vars
                $inLoopVarsList = (isset($reserved["loop"]))
                                ? $this->getLoop($reserved["loop"], $module, $params)
                                : array('_NOLOOP_'=>$resource->loopVars);
                // template
                if (isset($reserved['template'])) {
                    if ( ($template = $this->getTemplate($reserved['template'], $module))!==false) {
                        $inTag = $template; // replace inTag to template
                    }
                }
                // check
                if ($this->boolStr($reserved['check'], false)) {
                    $this->checkResourceLog($inResource, $inLoopVarsList);
                }
                // normaly single / multi by loop vars / zero loop , if loop return empty array.
                foreach($inLoopVarsList as $key => $inResource->loopVars) {
                    $inResource->loopkey = ($key !== '_NOLOOP_') ? $key : '';
                    // recursive fetch inside
                    $output .= $this->fetch($inTag, $inResource);
                }
                $this->line = ($resource->line += substr_count($match, "\n"));
                // close tag process
                // close module
                if ($flagModule && $forceClose ) {
                    if ($inModule !== 'GLOBAL' ) {
                        $this->closeModule($module);
                    } else {
                        $this->log(E_WARNING, "GLOBAL module can not be forced close", true, $module);
                    }
                }
            } // end of if parse on/off
           // forward source
            $source = substr($source, $pos+$len );
        } // end of while serch <tag>

        // remaining non-match string 
        $output .= $this->varFetch($source, $resource);
        $this->line = ( $resource->line += substr_count($source, "\n"));

        // post-process global fetch
        if ($flagGlobal){
            // unset all module instance 
            $modules = array_reverse($this->modules);
            foreach (array_keys($modules) as $modulename){
                $this->closeModule($modulename);
            }
            $this->line = 0;
        }
        return $output;
    }
    /**
     * parse tag attribute    ex. attr1="foo" attr2={@m:id|r@}
     * @param  string $source
     * @param  object $resource
     * @return array
     */
    protected function attributeParse($source, ParseResource $resource)
    {
        $attrs = array(
            'reserved' => array(
                "module"        => null,
                "newmodule"     => null,
                "refresh"       => null,
                "close"         => null,
                "method"        => null,
                "loop"          => null,
                "parse"         => null,
                "template"      => null,
                "check"         => null,
            ),
            'params'  => array(),
            'appends' => array(),
        );
        $pattern = "/(?:\"[^\"]*\"|'[^']*'|[^'\"\s]+)+/";
        if (preg_match_all( $pattern,$source,$matches)) {
            $array = $matches[0];
            $valid_pattern    = "/(?|(\w+)|(\[\w+\]))=(\"[^\"]*\"|'[^']*'|[^'\"\s]+)/";
            $reserved_pattern = "/^(".self::RESERVED_ATTRS.")$/i";
            $varkey_pattern   = "/^\[(\w+)\]$/i";

            foreach($array as $v) {
                // valid attribute
                if (preg_match($valid_pattern, $v, $sp_match)){
                    $key   = $sp_match[1];   // foo or [foo]
                    $value = $sp_match[2];   // 'bar' or {@name}

                    // sepalate reserved attribute
                    $parentkey = 'params';
                    if (preg_match($reserved_pattern, $key, $attr_match)){
                        $parentkey = 'reserved';
                        $key = strtolower($key);
                    } else {
                        if (preg_match($varkey_pattern, $key, $varkey_match)) {
                            $parentkey = 'appends';
                            $key = $varkey_match[1];
                        }
                    }
                    if (($ret=$this->removeQuote($value)) !== false) {
                        $value = $ret;
                    } else {
                        // un quate value, try for fetch {@VARIABLE}
                        $value = $this->varFetch($value, $resource);
                    }
                    $attrs[$parentkey][$key] = $value;
                } else {
                    // Unvalid attribute
                    $this->log(E_WARNING, "Unvalid attribute (".$v.")", true, $resource->module);
                }
            } // end of foreach
        }
        return $attrs;
    }
    /**
     * variable fetch .  search {@scope:name|format} , deployment to the value
     * @param  string $source 
     * @param  object $resource
     * @return string
     */
    protected function varFetch($source, ParseResource $resource)
    {
        $pattern = "/{@(?|(".self::VARIABLE_SCOPES."):|())(\w+)(?|\[(\w*)\]|())(?|\|(".self::OUTPUT_FORMATS.")|())}/i";
        $pattern = "/{@(?|(".self::VARIABLE_SCOPES."):|())(\w+)(?|((?:\[[^\[\]]+\])+)|())(?|\|(".self::OUTPUT_FORMATS.")|())}/i";
        $output = "";
        while (preg_match($pattern, $source, $matches, PREG_OFFSET_CAPTURE)){
            $match = $matches[0][0];
            $pos   = $matches[0][1];
            $len   = strlen($match);

            $scope   = $matches[1][0];
            $key     = $matches[2][0];
            $index   = $matches[3][0];
            $format  = $matches[4][0];

            $index_array = $this->squarebracketExplode($index);

            // Before the string of match
            $output .= $this->buffer(substr($source, 0, $pos));
            $this->line = ($resource->line += substr_count(substr($source, 0, $pos), "\n"));
            // --- parse variable priority ---
            //  1.methodVars   2.$loopVars   3.moduleVars   4.globalmoduleVars
            $scope = ($scope == "") ? "*" : strtoupper($scope[0]);

            $var = null;
            switch ($scope) {
                case "*":
                    $var = $this->getValueByDeepkey($key, $index_array, $resource->methodVars);
                    if (isset($var)){
                        break;
                    } // else no break
                case "L":
                    if ($key=='LOOPKEY') {
                        $var = $resource->loopkey;
                    } else {
                        $var = $this->getValueByDeepkey($key, $index_array, $resource->loopVars);
                    }
                    if (isset($var) || $scope == "L") { 
                        break;
                    } // else no break
                case "M": 
                    $var = $this->getValueByDeepkey($key, $index_array, $this->getVariable(null, $resource->module));
                    if (isset($var) || $scope == "M" || $resource->module == 'GLOBAL') {
                        break;
                    } // else no break
                case "G":
                    $var = $this->getValueByDeepkey($key, $index_array, $this->getVariable(null, 'GLOBAL'));
                    break;
            }
            if ( is_null($var) && $index !== "") {
                $this->log(E_PARSE, 'Not Found Variable array index ['.$index.'] is Unvalid '.$match, true, $resource->module);
            }
            if (isset($var)) {
                //format
                $output .= $this->buffer($this->format($var, $format));
            } else {
                $this->log(E_PARSE,'Not Found Variable'.$match, true, $resource->module);
                if ($this->debug()) {
                    $output .= $this->buffer("*NotFound*".$match);
                } else {
                    // $output .= $match;
                }
            }
            // remaining non-match string 
            $source = substr($source, $pos + $len);
        }
        $output .= $this->buffer($source);
        return $output;
    }
    /**
     * get value  array[key] / array[key][index]
     * @param  string $key
     * @param  array $index_array
     * @param  array|object  $array
     * @return string|null
     */
    protected function getValueByDeepkey($key, $key_array, $array)
    {
        if (isset($array[$key])) {
            $var = $array[$key];
        } else {
            return null;
        }
        if (empty($key_array)) {
            return $var;
        } else {
            foreach ($key_array as $index) {
                if ((is_array($var) || $var instanceof \ArrayAccess ) && isset($var[$index])) {
                    $var = $var[$index];
                } elseif (is_object($var) && property_exists($var, $index)) {
                    $var = $var->$index;
                } else {
                    return null;
                }
                if (is_null($var)) {
                    return null;
                }
            }
        }
        return $var;
    }
    /**
     * suarebracketExplode   
     * @param  string $source  [a1][a2]...
     * @return array           array (a1,a2, ...)
     */
    protected function squarebracketExplode($source)
    {
        if (preg_match_all("/\[([^\[\]]+)\]/", $source, $matches)) {
            return $matches[1];
        }
        return array();
    }
    /**
     * convert format 
     * @param  mixed  $source  string|array
     * @param  string $format 
     * @return string|false
     */
    protected function format($source, $format = 'h')
    {
        if ($source === false) {
            return false;
        }
        if (is_object($source) && ! method_exists($source,'__toString')) {
            $this->log(E_PARSE,'Cannot convert from object ('.get_class($source).') to string ');
            if ($this->debug()) {
                return "*Object*";
            }
            return false;
        }

        $format = ($format=="") ? "h" : strtolower($format)[0];
        switch ($format) {
            case 'h':
                $output = htmlspecialchars((string) $source, ENT_QUOTES, 'UTF-8');
                break;
            case 'r':
                $output = (string) $source;
                break;
            case 'u':
                $output = urlencode((string) $source);
                break;
            case 'j':
                $output = json_encode($source);
                break;
            default:
                $this->log(E_WARNING, "Unvalid format (".$format.")");
                $output = $this->format($source, 'h');
        }
        return $output;
    }
    // Error for debug -------------------------------------------
    /**
     * return log report
     * @return string|fale
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
    public function log($level, $message, $escape = true, $module = "")
    {
        if ($this->debug()) {
            $this->logger->log($level, $message, $escape, $module);
        }
    }
    public function checkResourceLog(ParseResource $resource, $inLoopVarsList) {
        if ($this->debug()) {
            $module = $resource->module;
            $check  = "<ul>";
            $check .= " <li>Method variables\n".(string) new ArrayDumpTable($resource->methodVars)."</li>";
            $check .= " <li>Loop variables\n".(string) new ArrayDumpTable($inLoopVarsList)."</li>";
            $check .= " <li>{$module} Module variables\n".(string) new ArrayDumpTable($this->getVariable(null, $module))."</li>";
            if ($module !== 'GLOBAL') {
                $check .= " <li>GLOBAL Module variables\n".(string) new ArrayDumpTable($this->getVariable(null, 'GLOBAL'))."</li>";
            }
            $check .= "</ul>";
            $this->log(E_DEPRECATED, $check, false, $module);
        }
    }
    // ---- Utility -------------------------------------------------
    // @todo review  move to static class
    /**
     * remove Quote ' ' or " "
     * @param  string $source 
     * @return string|false
     */
    protected function removeQuote($source)
    {
        $pattern = "/^(?|\"([^\"]*)\"|'([^']*)')$/";
        if (preg_match( $pattern, $source, $matches))
        {
            return $matches[1];
        }
        return false;
    }
    /**
     * boolStr
     * @param  string $str 
     * @param  bool   $default 
     * @return bool
     */
    public function boolStr($str, $default = false)
    {
        if (is_bool($str)){
            return $str;
        }
        //  yes|no , y|n  ,on|off    other return default
        if (! is_string($str)) {
            return $default;
        }
        if (preg_match("/^(y|on|yes)$/i",$str)) {
            return true;
        }
        if (preg_match("/^(n|no|off)$/i",$str)) {
            return false;
        }
        return $default;
    }
    /**
     * nearly array_merge. 
     * some difference  append int-key renumbering-key, override same key's value by source.
     * @see    __construct()
     * @param  array $root
     * @param  array $source 
     * @return array
     */
    public function arrayOverride(array $root, array $source)
    {
        foreach ($source as $key => $value ) {
            if (array_key_exists($key, $root)) {
                if (is_int($key)) {
                    $root[] = $value;
                } else {
                    if (is_array($value)) {
                        $root[$key] = $this->arrayOverride($root[$key], $value);
                    } else {
                        $root[$key] = $value;
                    }
                }
            } else {
                $root[$key] = $value;
            }
        }
        return $root;
    }

} // end of Agent class 
