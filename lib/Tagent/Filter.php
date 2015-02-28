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

        if (($name = Utility::removeQuote($this->name))===false) {
            $name = preg_quote($this->name);
        }
        if (($short = Utility::removeQuote($this->short))===false) {
            $short = preg_quote($this->short);
        }
        $this->pattern = ($short=='') ? array($name) : array($name, $short) ;
    }
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
