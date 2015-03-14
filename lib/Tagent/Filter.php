<?php
/**
 * Filter class, part of Tagent
 */
namespace Tagent;
/**
 * Filter entry object
 * @package Tagent
 */
class Filter
{
    /**
     * @var string filter name
     */
    protected $name = null;
    /**
     * @var string filter short name
     */
    protected $short = null;
    /**
     * @var mixed filter callable 
     */
    public    $callable = null;
    /**
     * @var string Regular expression pattern for filter
     */
    protected $pattern = null;
    /**
     * @var array Filter short name and name
     */
    protected $patterns = null;
    /**
     * constructor
     * @param  string $name 
     * @param  string $short 
     * @param  mixed $callable callable 
     * @return void
     */
    public function __construct($name, $short, callable $callable)
    {
        $this->name     = $name;
        $this->short    = $short;
        $this->callable = $callable;

        $name  = Utility::removeDelimter($name);
        $short = Utility::removeDelimter($short);
        $this->patterns = ($short == '') ? array($name) : array($short, $name ) ;
        $this->pattern = "/^(".implode('|', $this->patterns).")$/";
    }
    /**
     * filter
     * @param mixed $str 
     * @param string $filterName 
     * @return mixed
     */
    public function filter($str, $filterName)
    {
        $callable = $this->callable;
        return $callable($str, $filterName);
    }
    /**
     * getpatterns
     * @return array
     */
    public function getPatterns()
    {
        return $this->patterns;
    }
    /**
     * match name
     * @param  string $name 
     * @return bool
     */
    public function isMatch($name)
    {
        return (preg_match($this->pattern, $name)) ? true : false;
    }

}
