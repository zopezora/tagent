<?php
/**
 * Filter entry, part of Tagent
 * @package Tagent
 */
namespace Tagent;

class Filter
{
    /**
     * @var string
     */
    protected $name = null;
    /**
     * @var string
     */
    protected $short = null;
    /**
     * @var mixed callable
     */
    public    $callable = null;
    /**
     * @var string
     */
    protected $pattern = null;
    /**
     * @var array
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

        if (($name = Utility::removeDelimter($this->name))===false) {
            $name = preg_quote($this->name);
        }
        if (($short = Utility::removeDelimter($this->short))===false) {
            $short = preg_quote($this->short);
        }
        $this->patterns = ($short=='') ? array($name) : array($short, $name ) ;
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
