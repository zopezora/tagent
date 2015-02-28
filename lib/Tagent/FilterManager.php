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
                return (isset($str)) ? htmlspecialchars((string) $str, ENT_QUOTES, 'UTF-8') : null;
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
        // printf
        $this->filters[] = new Filter('/pf'.Utility::IN_QUOTE_PATTERN.'/', '',
            function($str, $name) {
                preg_match('/^pf('.Utility::IN_QUOTE_PATTERN.')$/', $name, $match);
                $format = Utility::removeQuote($match[1]);
                return (isset($str)) ? sprintf($format, $str) : null;
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
        foreach ($this->filters as $filter) {
            $patterns = array_merge($patterns, $filter->getPattern());
        }
        usort($patterns,function($a, $b){
            return (strlen($a) - strlen($b));
        });
        return $this->pattern = implode('|',$patterns);
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
