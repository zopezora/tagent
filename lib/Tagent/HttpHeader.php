<?php
/**
 * HttpHeader, part of Tagent
 * shortcut http header
 * @package Tagent
 */
namespace Tagent;

class HttpHeader
{
    /**
     * @var string
     */
    public $name;
    /**
     * @var string
     */
    public $header;
    /**
     * @var string
     */
    public $charset = false;

    /**
     * set property
     * @param string $name 
     * @param string $header 
     * @param bool $charset 
     * @return void
     */
    public function __construct($name, $header, $charset = false)
    {
        $this->name    = $name;
        $this->header  = $header;
        $this->charset = $charset;
    }
}
