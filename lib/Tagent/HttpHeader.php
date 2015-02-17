<?php
/**
 * HttpHeader, part of Tagent
 * shortcut http header
 * @package Tagent
 */
namespace Tagent;

class HttpHeader
{
    // @todo header short
    const HEADER_TEXT_SHORT        = 'plain|html|css';
    const HEADER_APPLICATION_SHORT = 'javascript|json|xml';
    const HEADER_IMAGE_SHORT       = 'jpeg|gif|png';
    /**
     * @var string charset
     */
    public static $charset = 'utf-8';
    /**
     * header
     * @param type $header 
     * @return type
     */
    public static function header($header)
    {
        // Content-Type: text/
        if (preg_match('/^('.self::HEADER_TEXT_SHORT.')$/i', $header, $match)) {
            return 'Content-Type: text/'.$match[0].'; charset='.self::$charset;
        } elseif (preg_match('/^('.self::HEADER_APPLICATION_SHORT.')$/i', $header, $match)) {
            return 'Content-Type: application/'.$match[0].'; charset='.self::$charset;
        } elseif (preg_match('/^('.self::HEADER_IMAGE_SHORT.')$/i', $header, $match)) {
            return 'Content-Type: image/'.$match[0];
        }
        return $header;
    }
}
