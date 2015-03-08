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

        if (($name = $this->removeDelimter($this->name))===false) {
            $name = preg_quote($this->name);
        }
        if (($short = $this->removeDelimter($this->short))===false) {
            $short = preg_quote($this->short);
        }
        $this->pattern = ($short=='') ? array($name) : array($short, $name ) ;
    }

    /**
     * remove regular expression delimiter
     * @param string $str 
     * @return string
     */
    public function removeDelimter($str)
    {
        if (preg_match('/^\/(.*)\/$/', $str, $match)) {
            return $match[1];
        }
        return false;
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
     * getpattern
     * @return array
     */
    public function getPattern()
    {
        return $this->pattern;
    }
    /**
     * match name
     * @param  string $name 
     * @return bool
     */
    public function isMatch($name)
    {
        $pattern = "/^(".implode('|', $this->pattern).")$/";
        return (preg_match($pattern, $name)) ? true : false;
    }

}
