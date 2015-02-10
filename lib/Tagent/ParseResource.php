<?php
/**
 * Resource container for parse, part of Tagent
 * @package Tagent
 */
namespace Tagent;

class ParseResource {
    /**
     * @var string
     */
    public $module = 'GLOBAL';
    /**
     * @var array
     */
    public $methodVars = array();
    /**
     * @var array
     */ 
    public $loopVars = array();
    /**
     * @var string
     */ 
    public $loopkey = '';
    /**
     * @var integer
     */ 
    public $line = 0;
    /**
     * constructor
     * @param integer $line 
     * @return void
     */
    public function __construct($line = 0)
    {
        $this->line = $line;
    }
}
