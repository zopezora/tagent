<?php
/**
 * Filter Manager, part of Tagent
 * Filter contenaire, registor, excute 
 * @package Tagent
 */
namespace Tagent;

class FilterManager 
{

    /**
     * @var array  filter object contenaire
     */
    protected $filters = array();
    /**
     * @var string 
     */
    public $pattern = '';
    /**
     * constructor set default filter
     * @return void
     */
    public function __construct() 
    {
        // html
        $this->filters[] = new Filter('html','h',
            function($str, $name) {
                $agent = Agent::self();
                $charset = $agent->getConfig('charset');
                return (isset($str)) ? htmlspecialchars((string) $str, ENT_QUOTES, $charset) : null;
            }
        );
        // raw
        $this->filters[] = new Filter('raw','r',
            function($str, $name) {
                return (isset($str)) ? (string) $str : null;
            }
        );
        // url
        $this->filters[] = new Filter('url','u',
            function($str, $name) {
                return (isset($str)) ? urlencode((string) $str) : null;
            }
        );
        // json
        $this->filters[] = new Filter('json','j',
            function($str, $name) {
                return (isset($str)) ? json_encode($str) : null;
            }
        );
        // base
        $this->filters[] = new Filter('base64','b',
            function($str, $name) {
                return (isset($str)) ? base64_encode($str) : null;
            }
        );
        // nl2br
        $this->filters[] = new Filter('nl2br','br',
            function($str, $name) {
                return (isset($str)) ? nl2br($str) : null;
            }
        );
        // space
        $this->filters[] = new Filter('nbsp','',
            function($str, $name) {
                return (isset($str)) ? str_replace(' ', '&nbsp;', $str) : null;
            }
        );
        // format by printf
        $this->filters[] = new Filter('/f'.Utility::IN_QUOTE_PATTERN.'/', '',
            function($str, $name) {
                preg_match('/^f('.Utility::IN_QUOTE_PATTERN.')$/', $name, $match);
                $format = Utility::removeQuote($match[1]);
                return (isset($str)) ? sprintf($format, $str) : null;
            }
        );
        // basic arithmetic operations
        $this->filters[] = new Filter('/(?:\+|-|\*|\/|%|\*\*|\^)(?:\d+)(?:\.\d+|)/', '',
            function($str, $name) {
                preg_match('/(\+|-|\*|\/|%|\*\*|\^)((?:\d+)(?:\.\d+|))/', $name, $match);
                $op    = $match[1];
                $param = $match[2];
                switch ($op) {
                    case "+":
                        return $str + $param;
                    case "-":
                        return $str - $param;
                    case "*":
                        return $str * $param;
                    case "/":
                        return $str / $param;
                    case "%":
                        return $str % $param;
                    case "**":
                    case "^":
                        return pow($str, $param);
                }
                return $str;
            }
        );
        // init pattern
        $this->setPattern();
    }
    /**
     * add filter object
     * @param name $name 
     * @param short $short 
     * @param mixed callable $callable 
     * @return void
     */
    public function addFilter($name, $short, callable $callable)
    {
        $this->filters[] = new Filter($name, $short, $callable);
        $this->setPattern();
    }
    /**
     * Regular expression pattern (w/o delimiter)
     * @return string
     */
    public function setPattern()
    {
        $patterns = array();
        $lens = array();
        foreach ($this->filters as $filter) {
            foreach($filter->getPattern() as $pattern) {
                $patterns[] = $pattern;
                $lens[] = strlen($pattern);
            }
        }
        asort($lens);
        $sorted = array();
        foreach($lens as $key => &$len) {
            $sorted[] = $patterns[$key];
        }
        return $this->pattern = implode('|',$sorted);
    }
    /**
     * get pattern
     * @return string
     */
    public function getPattern(){
        return $this->pattern;
    }
    /**
     * execute filter 
     * @param string $str 
     * @param string $name 
     * @return mixed
     */
    public function filter($str, $name)
    {
        foreach($this->filters as $filter) {
            if ($filter->isMatch($name)) {
                $callable = $filter->callable;
                return $callable($str, $name);
            }
        }
        return $str;
    }
}
