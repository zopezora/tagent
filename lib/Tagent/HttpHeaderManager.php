<?php
/**
 * HttpHeaderManager, part of Tagent
 * shortcut http header
 * @package Tagent
 */
namespace Tagent;

class HttpHeaderManager
{
    /**
     * @var object HttpHeader
     */
    public $headers = array();
    /**
     * @var string charset
     */
    public $charset = '';
    /**
     * constructor
     * @return void
     */
    public function __construct($charset = 'utf-8')
    {
        $this->charset = $charset;
        // text default
        $names = array('plain','html','css');
        foreach($names as $name) {
            $header = 'Content-Type: text/'.$name;
            $this->addHeader(new HttpHeader($name, $header, true));
        }
        // application
        $names = array('javascript','json','xml');
        foreach($names as $name) {
            $header = 'Content-Type: application/'.$name;
            $this->addHeader(new HttpHeader($name, $header, false));
        }
        // image
        $names = array('jpeg','gif','png');
        foreach($names as $name) {
            $header = 'Content-Type: image/'.$name;
            $this->addHeader(new HttpHeader($name, $header, false));
        }
        $this->addHeader(new HttpHeader('svg', 'Content-Type: image/svg+xml', true));
    }
    /**
     *  add header
     * @param object HttpHeader $header 
     * @return void
     */
    public function addHeader(HttpHeader $header)
    {
        foreach ($this->headers as $h) {
            if ($h->name == $header->name) {
                $this->headers[$name] = $header;
                return;
            }
        }
        $this->headers[] = $header;
    }
    /**
     * return header string , searching registered header
     * @param string $header 
     * @return string
     */
    public function header($name)
    {
        foreach($this->headers as $header) {
            if ($header->name == $name) {
                $charset = ($header->charset) ? '; charset='.$this->charset : '';
                return $header->header.$charset;
            }
        }
        return $name;
    }
    public function setCharset($charset)
    {
        $this->charset = $charset;
    }
}
