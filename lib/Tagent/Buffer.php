<?php
/**
 * Buffer, part of Tagent
 * @package Tagent
 */
namespace Tagent;

class Buffer {
    /**
     * @var string
     */
    public $buffer = '';
    /**
     * buffer
     * @param  string $str 
     * @return void
     */
    public function buffer($str)
    {
        $this->buffer .= $str;
        return $str;
    }
    /**
     * clear
     * @param  string $str 
     * @return void
     */
    public function clear($str)
    {
        $this->buffer = '';
    }
    /**
     * to string 
     * @return string
     */
    public function __toString(){
        return $this->buffer;
    }
}