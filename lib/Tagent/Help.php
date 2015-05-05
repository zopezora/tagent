<?php
namespace Tagent;

class Help
{
    public $modules = array();

    const STYLE = <<<'STYLE'
<style>
    p.HelpHeading {
        margin : 0;
        padding: 8px 0 0 0;
    }
    span.HelpModule {
        font-size  : 150%;
        font-style : bold;
        padding: 0 1em 0 0;
    }
    table.HelpTable {
        border-collapse : collapse;
        margin          : 1px;
    }
    table.HelpTable th {
        text-align : center ;
        background-color: #bbb;
        border: 1px solid #aaa;
    }
    table.HelpTable td {
        background-color : #fff;
        border           : 1px solid #aaa;
        padding : 2px 5px;
    }
    table.HelpTable td.HelpTableKey {
        background      : #eee;
    }
</style>

STYLE;
    /**
     * log output Help report, search module and method
     * @return void
     */
    public function __construct()
    {
        $agent = Agent::self();

        $output = '';
        $agentDir = $agent->getConfig('agent_directories');

        // search module
        $modules = array();
        $global = false;
        foreach ($agentDir as $dir) {
            if (($list = scandir($dir)) !== false ) {
                foreach ($list as $entry) {
                    $path = $dir.$entry;
                    if (is_dir($path)) {
                        if ('Module_' === substr($entry,0,7)) {
                            $module = substr($entry, 7);
                            if ($module !== 'GLOBAL' ){
                                $this->modules[] = array('name' => $module, 'path' => $path);
                            } else {
                                $global = array('name' => $module, 'path' => $path);
                            }
                        }
                    }
                }
            }
        }
        if ($global !== false) {
            array_unshift($this->modules, $global);
        }
        foreach ($this->modules as &$m) {
            // RefrectionMethodObject
            $pulls = array();
            $loops = array();

            // Module class
            $class = 'Module_'.$m['name']."\\Module";
            $exist = is_readable($m['path'].DIRECTORY_SEPARATOR."Module.php");
            if ($exist) {
                // Exist Module class
                $refclass = new \ReflectionClass($class);
                $methods = $refclass->getMethods(\ReflectionMethod::IS_PUBLIC);
                foreach($methods as $method) {
                    $methodname = $method->name;
                    if(strlen($methodname)> 4 && substr($methodname, 0, 4) == 'pull') {
                        $pulls[] = $method;
                    } elseif (strlen($methodname)> 4 && substr($methodname, 0, 4) == 'loop') {
                        $loops[] = $method;
                    }
                }
            } else {
                // Not exist Module class
            }

            // Pull class
            $pullsDir = $m['path'].DIRECTORY_SEPARATOR.'Pulls';
            if (is_dir($pullsDir)) {
                $methods = $this->searchClassMethod('pull', $pullsDir, $m['name']);
                $pulls   = array_merge($pulls, $methods);
            }

            // Loop class
            $loopsDir = $m['path'].DIRECTORY_SEPARATOR.'Loops';
            if (is_dir($loopsDir)) {
                $methods = $this->searchClassMethod('loop', $loopsDir, $m['name']);
                $loops   = array_merge($loops, $methods);
            }

            $m['pulls'] = $pulls;
            $m['loops'] = $loops;
        } // end of foreach $modules
    }

    /**
     * Module report html
     * @return string
     */
    public function reportHtml()
    {
        $output = '';
        $encoding = Agent::self()->getConfig('encoding');

        foreach ($this->modules as $m) {
            // Output for a module
            $output .= "<p class='HelpHeading'>\n";
            $output .= "Module:";
            $output .= ' <span class="HelpModule">'.$m['name']."</span>\n";
            $output .= ' <span class="HelpPath">('.$m['path']."/)</span>\n";
            $output .= "</p>\n";

            $output .= "<table class='HelpTable'>\n";

            $output .= " <tr>\n";
            $output .= "  <th colspan='3'>Pull</th>\n";
            $output .= " </tr>\n";
            foreach ($m['pulls'] as $pull) {
                $output .= " <tr>\n";
                $output .= '  <td class="HelpTableKey">'.$this->getAttrReport($pull).'</td>';
                $output .= '  <td>'.$this->getMethodReport($pull).'</td>';
                $anotation = $this->getAnotationReport($pull, "\n");
                $anotation  = nl2br(htmlspecialchars($anotation, ENT_QUOTES, $encoding));
                $output .= '  <td>'.$anotation.'</td>';
                $output .= " </tr>\n";
            }

            $output .= " <tr>\n";
            $output .= "  <th colspan='3'>Loop</th>\n";
            $output .= " </tr>\n";
            foreach ($m['loops'] as $loop) {
                $output .= " <tr>\n";
                $output .= '  <td class="HelpTableKey">'.$this->getAttrReport($loop).'</td>';
                $output .= '  <td>'.$this->getMethodReport($loop).'</td>';
                $anotation = $this->getAnotationReport($loop, "\n");
                $anotation  = nl2br(htmlspecialchars($anotation, ENT_QUOTES, $encoding));
                $output .= '  <td>'.$anotation.'</td>';
                $output .= " </tr>\n";
            }
            $output .= '</table>';
        } // end of foreach $modules
        return $output;
    }
    /**
     * Module report plain
     * @return string
     */
    public function reportPlain()
    {
        $output = '';
        foreach ($this->modules as $m) {
            $output .= "----------------------------------------------------------\n";
            $output .= 'Module: '.$m['name'].'  ('.$m['path']."/)\n";
            $output .= "  Pulls\n";
            foreach ($m['pulls'] as $pull) {
                $attr = $this->getAttrReport($pull);
                $space = (strlen($attr)<20) ? str_repeat('.',20-strlen($attr)): '';
                $output .= '    '.$attr.' '.$space.' ';
                $output .= $this->getMethodReport($pull)."\n";
                $anotation = trim($this->getAnotationReport($pull, "\n      "));
                if ($anotation) {
                    $output .= '       '.$anotation."\n";
                }
            }
            $output .= "  Loops\n";
            foreach ($m['loops'] as $loop) {
                $attr = $this->getAttrReport($loop);
                $space = (strlen($attr)<20) ? str_repeat('.',20-strlen($attr)): '';
                $output .= '    '.$attr.' '.$space.' ';
                $output .= $this->getMethodReport($loop)."\n";
                $anotation = trim($this->getAnotationReport($loop, "\n      "));
                if ($anotation) {
                    $output .= '       '.$anotation."\n";
                }
            }
        }
        return $output;
    }
    /**
     * Get Method Report from refrectinon method object
     * @param object \ReflectionMethod $method 
     * @return string
     */
    protected function getMethodReport(\ReflectionMethod $method)
    {
        $op = ($method->isStatic()) ? '::' : '->';
        $shortClass = $this->getShortClassReport($method);
        return $shortClass.$op.$method->name;
    }

    /**
     * Remove prefix method name pull_ or loop_
     * @param object \ReflectionMethod $method 
     * @return string
     */
    protected function getAttrReport(\ReflectionMethod $method)
    {
        $name = $method->name;
        $class = $method->class;
        $classPart = explode('\\', $class);
        $partCount = count($classPart);
        if ($classPart[$partCount-1] == 'Module') {
            // Module->pull_**** Module->loop_****
            $p = ($name[4] == '_') ? 5: 4;
            return substr($name, $p);
        }
        // Pulls/sub  Loops/sub
        return $classPart[$partCount-1];
    }
    /**
     * Get shot class report
     * @param object \ReflectionMethod $method 
     * @return string
     */
    protected function getShortClassReport(\ReflectionMethod $method)
    {
        $class = $method->class;
        return substr($class, strpos($class, '\\')+1);
    }
    /**
     * Get Anotation Report
     * @param object \ReflectionMethod $method 
     * @return string
     */
    protected function getAnotationReport(\ReflectionMethod $method, $nl)
    {
        // get @tag note
        $pattern = "/^\s*\*(?!\/)(.*)$/m";
        preg_match_all($pattern, $method->getDocComment(), $matches, PREG_SET_ORDER);
        $docs = '';
        foreach ($matches as $match) {
            $docs .= $match[1].$nl;
        }
        return $docs;
    }
    /**
     * Recusive search class and method
     * @param string $kind 
     * @param dir $dir 
     * @param string $module 
     * @param string $sub 
     * @return string
     */
    protected function searchClassMethod($kind, $dir, $module, $sub = '') {

        $agent = Agent::self();
        $list = scandir($dir);
        $namespace = 'Module_'.$module."\\".$kind."s\\";
        $methods = array();

        foreach($list as $entry) {
            if ($entry == '..' || $entry == '.') {
                continue;
            }
            $path = $dir.DIRECTORY_SEPARATOR.$entry;

            if (is_dir($path)) {
                // Recursive sub directory
                $sub .= $entry.'_';
                $ret = $this->searchClassMethod($kind, $path, $module, $sub);
                $methods = array_merge($methods,$ret);

            }
            if (is_file($path) && strlen($entry)>4 && substr($entry,-4) == '.php' ) {
                $name = substr($entry, 0, -4);
                $class = $namespace.$sub.$name;
                if (class_exists($class)) {
                    $refClass = new \ReflectionClass($class);
                    $methodnames[] = $kind.str_replace('_', '', $sub).$name;
                    $methodnames[] = $kind.'_'.$sub.$name;

                    $find = false;
                    foreach ($methodnames as $methodname) {
                        if ($refClass->hasMethod($methodname)) {
                            $method = $refClass->getMethod($methodname);
                            if ($method->isPublic()) {
                                $methods[] = $method;
                                $find = true;
                                break ;
                            }
                        }
                    }
                    if (! $find && $refClass->hasMethod('__invoke')) {
                        $method = getMethod('__invoke');
                        if ($method->isPublic()) {
                            $methods[] = $method;
                            break ;
                        }
                    }

                } // if class exist
            } // if found file

        } // foreach list dir
        return $methods;
    }
}
