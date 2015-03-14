<?php
/**
 * Buffer class, part of Tagent
 */
namespace Tagent;
/**
 * Buffer conteneire
 * @package Tagent
 */
class Buffer
{
    /**
     * @var string buffer string
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
    public function __toString()
    {
        return $this->buffer;
    }
}