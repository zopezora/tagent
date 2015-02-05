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
    const AGENT_TAG       = "ag";
    const AGENT_DIRECTORY = "ag/";
    const DEBUG           = false;
    const OB_START        = true;
    const LINE_OFFSET     = 0;
    // const pattern
    const RESERVED_ATTRS  = 'module|method|loop|parse|close|refresh|newmodule';
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
     * @var array  log contenaire  INFO NOTICE WARNING ERROR
     */
    protected $logs = array();
    /**
     * @var integer  line counter
     */
    protected $line = 0;
    /**
     * @var integer  line counter
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
            "agent_tag"       => self::AGENT_TAG,
            "agent_directory" => self::AGENT_DIRECTORY,
            "debug"           => self::DEBUG,
            "ob_start"        => self::OB_START,
            "line_offset"     => self::LINE_OFFSET,
        );
        // config override
        $this->configs = $this->arrayOverride( $this->configs, $config );
        // Module utoloader
        $this->loader = ModuleLoader::init($this->configs['agent_directory']);
        // debug
        if ( is_string($this->configs['debug'])) {
            $this->configs['debug'] = $this->boolStr($this->configs['debug']);
        }

        // Output-Buffer start
        $this->cwd = getcwd();
        if (is_string($this->configs['ob_start'])) {
            $this->configs['ob_start'] = $this->boolStr($this->configs['ob_start'], true);
        }
        if ($this->configs['ob_start']) {
            $callback = array($this, 'obBufferFlush');
            ob_start($callback);
            $this->buffercallback = $callback;
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
     * set debug
     * @param  bool|string $bool  true|false  'on|off' / 'yes|no' / 'y|n'
     * @return void
     */
    public function setDebug($bool)
    {
        if (is_string($bool)) {
            $bool = $this->boolStr($bool, false);
        }
        $this->configs['debug'] = ($bool) ? true : false ;
    }
    /**
     * return debug setting boolean
     * @return bool
     */
    public function getDebug()
    {
        return $this->configs['debug'];
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
     * set variables  always override
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
        if (isset($this->modules[$module]['objects'][$name])) {
            $object = $this->modules[$module]['objects'][$name];
            if ($object instanceof FactoryInterface) {
                $this->modules[$module]['objects'][$name] = $object->factory();
                return $this->modules[$module]['objects'][$name];
            }
            return $object;
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
        if (! isset($name)) {
            return;
        }
        if (! is_null($this->getModule($module))) {
            if (is_null($object) && isset($this->modules[$module]['objects'][$name])) { 
                unset ($this->modules[$module]['objects'][$name]);
            } else {
                $this->modules[$module]['objects'][$name] = $object;
            }
        } else {
            $this->log(E_WARNING, 'set '.$name.' module='.$module.' is not open yet');
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
        return (isset($this->modules[$module]['objects'][$key])) ? true : false;
    }
    // Module Control --------------------------------------------------------------------
    /**
     * Module namespace
     * @param  string $module 
     * @return string
     */
    public function getModuleNamespace($module) {
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
            $this->log(E_NOTICE, "Open Module",$module);
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
        $this->log(E_NOTICE, "new Open Module (".$module.")");
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
        $this->log(E_NOTICE, "Close Module", $module);
    }
    /**
     * return module instance
     * @param  array $module 
     * @return object|false|null  false...already open but no instance / null...open yet
     */
    public function getModule($module, $tryopen = false)
    {
        if (isset($this->modules[$module]['instance'])) {
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
        $classname = $this->getModuleNamespace($module)."\\Module";
        if (class_exists($classname)) {
            $this->modules[$module]['instance'] = new $classname($params);
            $this->log(E_NOTICE, 'CREATE Module Instance ('.get_class($this->modules[$module]['instance']).")",$module);
        } else {
            $this->log(E_NOTICE, 'Not Found Module class. ('.$classname.")",$module);
        }
        return $this->modules[$module]['instance'];
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
            $this->log(E_NOTICE, 'Module Refresh', $module);
        } else {
            $this->log(E_WARNING, 'Not exists refresh method in module method', $module);
        }
    }
    /**
     * modulename by Object    check top level namespace. Module_*** , return *** 
     * @see AbstractModule
     * @param  object $object 
     * @return string|false
     */
    public function getModuleNameByObject($object)
    {
        $class_array = explode("\\",get_class($object));
        if ( count($class_array)>1 && strpos($class_array[0], "Module_")==0) {
            return str_replace("Module_","",$class_array[0]);
        }
        return false;
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
     * @return array           case method: array() / case loop: array(array())
     */
    protected function callGetVariables($kind, $name, $module, $params, $default)
    {
        // first, search module method
        $md = $this->getModule($module);
        $methodname = $kind."_".$name;
        if (is_object($md) && method_exists($md, $methodname)) {
            $this->log(E_NOTICE, 'Call Module_'.$module.'->'.$methodname, $module);
            return (array) $md->$methodname($params);
        }
        // second, search method class   \Module_module\Method or Loops\name
        $classname = $this->getModuleNamespace($module)."\\".ucfirst($kind)."s\\".$name;
        if (class_exists($classname)) {
            $instance = new $classname($params);
            if (method_exists($instance, $methodname)) {
                $this->log(E_NOTICE, 'Call '.$classname.'->'.$methodname, $module);
                return (array) $instance->$methodname($params);
            }
            if (is_callable($instance)) {
                $this->log(E_NOTICE, 'Callable '.$kind.' '.$classname.'()', $module);
                return (array) $instance($params);
            }
        }
        $this->log(E_WARNING,'Not Found call ('.$module.'->'.$methodname.')', $module);
        return $default;
    }
    // fetch & parse   --------------------------------------------------------------------
    /**
     * callbak for ob_start()
     * @param  string $buffer 
     * @return string
     */
    public function obBufferFlush($buffer)
    {
        chdir($this->cwd);
        $output = $this->fetch($buffer);
        if ($this->configs['debug']) {
            $output .= $this->getLogReport();
        }
        return $output;
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
            ob_flush();
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
            if ($this->configs['debug']) {
                echo $this->getLogReport();
            }
        }
    }
    /**
     * main fetch . Retrieve nested 'agent tag' by recursive call. 
     * Processing according to the attribute. module control, get array variables. etc..
     * @param  string  $source 
     * @param  array   $item     $item['NAME'] is expanded, provide for {@NAME} in template 
     * @return string 
     */
    public function fetch($source, $module='GLOBAL', $methodVars = array(), $loopVars = array(), $line = 0)
    {
        // pre-process  global fetch
        $flagGlobal = false;
        if (is_null($this->getModule('GLOBAL'))) {
            $this->openModule('GLOBAL');
            $flagGlobal = true;
        }
        if ($line == 0){
            $this->line = ($line = 1);
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
            $output .= $this->varFetch(substr($source, 0, $pos), $module, $methodVars, $loopVars, $line);
            $this->line = ($line += substr_count(substr($source, 0, $pos), "\n"));

            // inside Tag
            // attrs ['params'] / ['reserved'] / ['appends']
            $attrs = $this->attributeParse($attr, $module, $methodVars, $loopVars); 
            $reservd = $attrs['reserved'];

            // parse switch parse= on/off yes/no
            if ( ! $this->boolStr($reservd['parse'], true) ) {
                // parse = no
                $output .= $inTag;
                $this->log(E_NOTICE,'Parse: No', $module);
            } else {
                // parse = yes
                //module control
                $inModule    = (isset($reservd["module"]))
                                ? $reservd["module"]
                                : $module;
                $flagModule  = (isset($reservd["module"]))
                                ? $this->openModule($inModule, $attrs['params'])
                                : false;
                $forceCloseModule = $this->boolStr($reservd['close'], false);

                if ($this->boolStr($reservd['refresh'], false)) {
                    $this->refreshModule($inModule,$attrs['params']);
                }
                // method vars
                $inMethodVars = (isset($reservd["method"])) 
                                ? $this->getMethod($reservd["method"], $inModule, $attrs['params']) 
                                : $methodVars;
                // appends vars
                foreach ($attrs['appends'] as $key => $value) {
                    $inMethodVars[$key] = $value;
                }
                // loop vars
                $inLoopVarsList = (isset($reservd["loop"]))
                                ? $this->getLoop($reservd["loop"], $inModule, $attrs['params'])
                                : array('noloop'=>$loopVars);
                // normaly single / multi by loop vars / zero loop
                foreach($inLoopVarsList as $key => $inLoopVars) {
                    $inLoopVars['LOOPKEY']=$key;
                    // recursive fetch
                    $output .= $this->fetch($inTag, $inModule, $inMethodVars, $inLoopVars, $line);
                }
                $this->line = ($line += substr_count($match, "\n"));
                // close tag process
                // close module
                if ($flagModule && $forceCloseModule ) {
                    $this->closeModule($inModule);
                }
            } // end of if parse on/off
           // forward source
            $source = substr($source, $pos+$len );
        } // end of while serch <tag>

        $output .= $this->varFetch($source, $module, $methodVars, $loopVars, $line);
        $this->line = ( $line += substr_count($source, "\n"));

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
     * @param  string $module
     * @param  array  $methodVars
     * @param  array  $loopVars
     * @return array
     */
    protected function attributeParse($source, $module, $methodVars, $loopVars)
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
                        $value = $this->varFetch($value, $module, $methodVars, $loopVars);
                    }
                    $attrs[$parentkey][$key] = $value;
                } else {
                    // Unvalid attribute
                    $this->log(E_WARNING, "Unvalid attribute (".$v.")", $module);
                }
            } // end of foreach
        }
        return $attrs;
    }
    /**
     * variable fetch .  search {@scope:name|format} , deployment to the value
     * @param  string $source 
     * @param  string $module 
     * @param  array  $methodVars 
     * @param  array  $loopVars 
     * @return string
     */
    protected function varFetch($source, $module, $methodVars, $loopVars, $line)
    {
        $pattern = "/{@(?|(".self::VARIABLE_SCOPES."):|())(\w+)(?|\[(\w*)\]|())(?|\|(".self::OUTPUT_FORMATS.")|())}/i";
        $output = "";
        while (preg_match($pattern, $source, $matches, PREG_OFFSET_CAPTURE)){
            $match = $matches[0][0];
            $pos   = $matches[0][1];
            $len   = strlen($match);

            $scope   = $matches[1][0];
            $key     = $matches[2][0];
            $index   = $matches[3][0];
            $format  = $matches[4][0];

            // Before the string of match
            $output .= substr($source, 0, $pos);
            $this->line = ($line += substr_count(substr($source, 0, $pos), "\n"));
            // --- parse variable priority ---
            //  1.methodVars   2.$loopVars   3.moduleVars   4.globalmoduleVars
            $scope = ($scope == "") ? "*" : strtoupper($scope[0]);

            $var = null;
            switch ($scope) {
                case "*":
                    $var = (isset($methodVars[$key])) ? $methodVars[$key] : null;
                    if (isset($var)){
                        break;
                    } // else no break
                case "L": 
                    $var = (isset($loopVars[$key])) ? $loopVars[$key] : null;
                    if (isset($var) || $scope == "L") { 
                        break;
                    } // else no break
                case "M": 
                    $var = $this->getVariable($key, $module);
                    if (isset($var) || $scope == "M" || $module == 'GLOBAL') {
                        break;
                    } // else no break
                case "G":
                    $var = $this->getVariable($key, 'GLOBAL');
                    break;
            }
            if ( isset($var) && $index !== "") {
                if (is_array($var)) {
                    $var = (isset($var[$index])) ? $var[$index] : null ;
                } else {
                    $this->log(E_WARNING, 'varFetch Not array index ['.$index.'] is Unvalid '.$match, $module);
                }
            } 
            if (isset($var)) {
                //format
                $output .= $this->format($var, $format);
            } else {
                $this->log(E_ERROR,'Not Found Variable'.$match, $module);
                if ($this->configs['debug']) {
                    $output .= "*ERROR*".$match;
                } else {
                    // $output .= $match;
                }
            }
            // remaining non-match string 
            $source = substr($source, $pos + $len);
        }
        $output .= $source;
        return $output;
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
     * @TODO   use template & css coloring.
     * @return string
     */
    public function getLogReport()
    {
        $output  = "<div style='clear:both; border:1px solid #F00;'>\n";
        if (empty($this->logs)) {
            $output .= "No Report\n";
        } else {
            $output .= "<table border=1>\n";
            $output .= " <tr>\n";
            $output .= "  <th>LINE</th>";
            $output .= "  <th>LEVEL</th>";
            $output .= "  <th>MODULE</th>";
            $output .= "  <th>MESSAGE</th>";
            $output .= " </tr>\n";
            foreach($this->logs as $log){
                $output .= " <tr>\n";
                $output .= "  <td>".htmlspecialchars($log['line'])."</td>\n";
                $output .= "  <td>".htmlspecialchars($log['level_str'])."</td>\n";
                $output .= "  <td>".htmlspecialchars($log['module'])."</td>\n";
                $output .= "  <td>".htmlspecialchars($log['message'])."</td>\n";
                $output .= " </tr>\n";
            }
            $output .= "</table>\n";
        }
        $output .= "</div>\n";
        return $output;
    }
    /**
     * log
     * @param  integer|string $level
     * @param  string $message
     * @param  string $module 
     * @return void
     */
    public function log($level, $message, $module = "")
    {
        // $level 1.ERROR 2.WARNING 4.PARSE 8.NOTICE     16384 E_USER_DEPRECATED
        if (is_string($level)) {
            $level_str = (is_string($level)) ? $level: $level;
            $level     = E_USER_DEPRECATED; // 0x4000
        } else {
            if (isset($this->loglevel[$level])) {
                $level_str = $this->loglevel[$level];
            } else {
                $level_str = "UNDEFINED";
                $level     = E_USER_DEPRECATED; // 0x4000
            }
        }
        $offset = ($this->line !=0 ) ? $offset = $this->getConfig('line_offset') : 0;

        $this->logs[] = array(
                    "line"      => $this->line + $offset,
                    "level"     => $level,
                    "level_str" => $level_str,
                    "module"    => $module,
                    "message"   => $message,
        );
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
