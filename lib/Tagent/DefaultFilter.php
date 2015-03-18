<?php
/**
 * DefaultFilter class, part of Tagent
 */
namespace Tagent;
/**
 * Default filter register
 * @package Tagent
 */
class DefaultFilter
{
    /**
     * html filter
     * @param string $str 
     * @param string $name 
     * @return mixed
     */
    public static function htmlFilter($str, $name)
    {
        $agent = Agent::self();
        $charset = $agent->getConfig('charset');
        return (isset($str)) ? htmlspecialchars((string) $str, ENT_QUOTES, $charset) : null;
    }
    /**
     * raw filter
     * @param string $str 
     * @param string $name 
     * @return mixed
     */
    public static function rawFilter($str, $name)
    {
        return (isset($str)) ? (string) $str : null;
    }
    /**
     * url filter
     * @param string $str 
     * @param string $name 
     * @return mixed
     */
    public static function urlFilter($str, $name)
    {
        return (isset($str)) ? urlencode((string) $str) : null;
    }
    /**
     * json filter
     * @param array $str
     * @param string $name 
     * @return mixed
     */
    public static function jsonFilter($str, $name)
    {
        return (isset($str)) ? json_encode($str) : null;
    }
    /**
     * base64 filter
     * @param string $str 
     * @param string $name 
     * @return mixed
     */
    public static function base64Filter($str, $name)
    {
        return (isset($str)) ? base64_encode($str) : null;
    }
    /**
     * nl2br filter
     * @param string $str 
     * @param string $name 
     * @return mixed
     */
    public static function nl2brFilter($str, $name)
    {
        return (isset($str)) ? nl2br($str) : null;
    }
    /**
     * nbsp filter
     * @param string $str 
     * @param string $name 
     * @return mixed
     */
    public static function nbspFilter($str, $name)
    {
        return (isset($str)) ? str_replace(' ', '&nbsp;', $str) : null;
    }
    /**
     * trim filter
     * @param string $str 
     * @param string $name 
     * @return mixed
     */
    public static function trimFilter($str, $name)
    {
        return (isset($str)) ? trim($str) : null;
    }
    /**
     * printf filter
     * '/f'.Utility::IN_QUOTE_PATTERN.'/'
     * @param string $str 
     * @param string $name 
     * @return mixed
     */
    public static function printfFilter($str, $name)
    {
        preg_match('/^f('.Utility::IN_QUOTE_PATTERN.')$/', $name, $match);
        $format = Utility::removeQuote($match[1]);
        return (isset($str)) ? sprintf($format, $str) : null;
    }
    /**
     * basic arithmetic operations filter
     * '/(?:\+|-|\*|\/|%|\*\*|\^)(?:\d+)(?:\.\d+|)/'
     * @param string $str 
     * @param string $name 
     * @return mixed
     */
    public static function arithmeticFilter($str, $name)
    {
        preg_match('/(\+|-|\*|\/|%|\*\*|\^)((?:\d+)(?:\.\d+|))/', $name, $match);
        $op    = $match[1];
        $param = $match[2];
        switch ($op) {
            case "+":
                return $str + $param;
            case "-":
                return $str - $param;
            case "*":
                return $str * $param;
            case "/":
                return $str / $param;
            case "%":
                return $str % $param;
            case "**":
            case "^":
                return pow($str, $param);
        }
        return $str;
    }
    public static function defaultFilter($str, $name)
    {
        if(is_null($str)) {
            preg_match('/^d('.Utility::IN_QUOTE_PATTERN.')$/', $name, $match);
            return Utility::removeQuote($match[1]);
        }
        return $str;
    }

}