<?php
/**
 * HttpHeader, part of Tagent
 * shortcut http header
 * @package Tagent
 */
namespace Tagent;

class HttpHeader
{
    public $name;

    public $header;

    public $charser = false;

    public function __construct($name, $header, $charset = false)
    {
        $this->name    = $name;
        $this->header  = $header;
        $this->charset = $charset;
    }
}
