<?php
/**
 * HttpHeader, part of Tagent
 */
namespace Tagent;
/**
 * http header object
 * @package Tagent
 */
class HttpHeader
{
    /**
     * @var string Hedder short-cut name  
     */
    public $name;
    /**
     * @var string headder string
     */
    public $header;
    /**
     * @var string charset for header
     */
    public $charset = false;
    /**
     * set property
     * @param string $name 
     * @param string $header 
     * @param bool $charset if true, add charset='***' in header 
     * @return void
     */
    public function __construct($name, $header, $charset = false)
    {
        $this->name    = $name;
        $this->header  = $header;
        $this->charset = $charset;
    }
}
