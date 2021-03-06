<?php
/**
 * FilterManager, part of Tagent
 */
namespace Tagent;
/**
 * Filter contenaire and operatopn, registor, excute 
 * @package Tagent
 */
class FilterManager 
{
    /**
     * @var array  filter object contenaire
     */
    protected $filters = array();
    /**
     * @var string Finaly regular expression pattern for variable parse filter part.
     */
    public $pattern = '';
    /**
     * constructor set default filter
     * @return void
     */
    public function __construct() 
    {
        require_once(__DIR__.DIRECTORY_SEPARATOR."DefaultFilter.php");
        $class = 'Tagent\DefaultFilter';

        // html
        $this->filters[] = new Filter('html',
                                      'h',  
                                       array($class, 'htmlFilter')
                                     );
        // raw
        $this->filters[] = new Filter('raw',
                                      'r',  
                                       array($class, 'rawFilter')
                                     );
        // url
        $this->filters[] = new Filter('url',
                                      'u',  
                                       array($class, 'urlFilter')
                                     );
        // json
        $this->filters[] = new Filter('json',
                                      'j',
                                       array($class, 'jsonFilter')
                                     );
        // base
        $this->filters[] = new Filter('base64',
                                      'b',
                                       array($class, 'base64Filter')
                                     );
        // nl2br
        $this->filters[] = new Filter('nl2br',
                                      'br',
                                       array($class, 'nl2brFilter')
                                     );
        // space to &nbsp;
        $this->filters[] = new Filter('nbsp',
                                      '',
                                       array($class, 'nbspFilter')
                                     );
        // trim
        $this->filters[] = new Filter('trim',
                                      't',
                                       array($class, 'trimFilter')
                                     );
        // default
        $this->filters[] = new Filter('/d'.Utility::IN_QUOTE_PATTERN.'/',
                                      '',
                                       array($class, 'defaultFilter')
                                     );
        // format by printf
        $this->filters[] = new Filter('/f'.Utility::IN_QUOTE_PATTERN.'/',
                                      '',
                                       array($class, 'printfFilter')
                                     );
        // basic arithmetic operations
        $this->filters[] = new Filter('/(?:\+|-|\*|\/|%|\*\*|\^)(?:\d+)(?:\.\d+|)/',
                                      '',
                                       array($class, 'arithmeticFilter')
                                     ); 
        // generate pattern
        $this->setPattern();
    }
    /**
     * add filter object
     * @param string $name 
     * @param string $short 
     * @param mixed $callable filter callable
     * @return void
     */
    public function addFilter($name, $short, callable $callable)
    {
        $this->filters[] = new Filter($name, $short, $callable);
        $this->setPattern();
    }
    /**
     * generate Regular expression pattern (w/o delimiter)
     * @return string
     */
    public function setPattern()
    {
        $patterns = array();
        $lens = array();
        foreach ($this->filters as $filter) {
            foreach($filter->getPatterns() as $pattern) {
                $patterns[] = $pattern;
                $lens[] = strlen($pattern);
            }
        }
        asort($lens);
        $sorted = array();
        foreach($lens as $key => &$len) {
            $sorted[] = $patterns[$key];
        }
        return $this->pattern = implode('|', $sorted);
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
