<?php
/**
 * Utility class, part of Tagent
 */
namespace Tagent;
/**
 * Common utility static method. 
 * @package Tagent
 */
class Utility
{
    const QUOTE_PATTERN = "/^(?|\"((?:\\\\\"|[^\"])*)\"|'((?:\\\\'|[^'])*)')$/";
    const IN_QUOTE_PATTERN = "(?:\"(?:\\\\\"|[^\"])*\"|'(?:\\\\'|[^'])*')";

    // ---- Utility -------------------------------------------------
    /**
     * remove Quote ' ' or " "
     * @param  string $str 
     * @return string|false
     */
    public static function removeQuote($str)
    {
        if (preg_match(self::QUOTE_PATTERN, $str, $matches))
        {
            return self::unescapeQuote($matches[1]);
        }
        return false;
    }
    /**
     * unescape Quote
     * @param  string $str 
     * @return string
     */
    public static function unescapeQuote($str) {
        $str = preg_replace("/\\\\'/", "'", $str);
        $str = preg_replace("/\\\\\"/", "\"", $str);
        return $str;
    }
    /**
     * remove regular expression delimiter Or preg_quote
     * @param string $str 
     * @return string
     */
    public static function removeDelimter($str)
    {
        if ($str != '' && $str[0] == '/' && substr($str, -1) == '/') {
            return substr($str, 1, -1);
        }
        return preg_quote($str);
    }
    /**
     * boolStr
     * @param  string $str 
     * @param  bool   $default 
     * @return bool
     */
    public static function boolStr($str, $default = false)
    {
        if (is_bool($str)) {
            return $str;
        }
        //  yes|no, y|n, on|off    other return default
        if (! is_string($str)) {
            return $default;
        }
        if (preg_match("/^(y|on|yes|true)$/i", $str)) {
            return true;
        }
        if (preg_match("/^(n|no|off|false)$/i", $str)) {
            return false;
        }
        return $default;
    }
    /**
     * nearly array_merge. 
     * int-keys are renumbering, override same key's value by source.
     * @param  array $root
     * @param  array $source 
     * @return array
     */
    public static function arrayOverride(array $root, array $source)
    {
        foreach ($source as $key => $value ) {
            if (array_key_exists($key, $root)) {
                if (is_int($key)) {
                    $root[] = $value;
                } else {
                    if (is_array($value)) {
                        $root[$key] = self::arrayOverride($root[$key], $value);
                    } else {
                        $root[$key] = $value;
                    }
                }
            } else {
                $root[$key] = $value;
            }
        }
        return $root;
    }
    /**
     * get value  array[key] / array[key][index]
     * @param  array $index_array
     * @param  array|object  $array
     * @return string|null
     */
    public static function getValueByDeepkey($key_array, $var)
    {
        foreach ($key_array as &$index) {
            if ((is_array($var) || $var instanceof \ArrayAccess ) && isset($var[$index])) {
                $var = $var[$index];
            } elseif (is_object($var) && property_exists($var, $index)) {
                $var = $var->$index;
            } else {
                return null;
            }
        }
        return $var;
    }
    /**
     * get caller class-method 
     * @param  integer $back 
     * @return array
     */
    public static function getCaller($back = 0) {
        $bt = debug_backtrace();
        $caller = array();
        $caller['file']      = (isset($bt[$back+1]) && isset($bt[$back+1]['file'])) ? $bt[$back+1]['file'] : '';
        $dirs                = ($caller['file']) ? explode(DIRECTORY_SEPARATOR, dirname($caller['file'])) : false;
        $parentdir           = ($dirs) ? end($dirs) : '';
        $caller['shortfile'] = ($dirs) ? $parentdir.DIRECTORY_SEPARATOR.basename($caller['file']) : '' ;
        $caller['line']      = (isset($bt[$back+1]) && isset($bt[$back+1]['line'])) ? $bt[$back+1]['line'] : '';
        $caller['class']     = (isset($bt[$back+2]) && isset($bt[$back+2]['class'])) ? $bt[$back+2]['class'] : '';
        $caller['type']      = (isset($bt[$back+2]) && isset($bt[$back+2]['type'])) ? $bt[$back+2]['type'] : '';
        $caller['function']  = (isset($bt[$back+2]) && isset($bt[$back+2]['function'])) ? $bt[$back+2]['function'] : '';
        $caller['classmethod'] = $caller['class'].$caller['type'].$caller['function'];
        $caller['fileline']    = $caller['shortfile'].'('.$caller['line'].')';
        return $caller;
    }

    /**
     * get value (scalar to string, or type for log
     * @param  mixed $value 
     * @return string
     */
    public static function getValueOrType($value) {
        if (is_scalar($value)) {
            return (string) "'".$value."'";
        }
        if (is_object($value)) {
            return "*".get_class($value)."*";
        }

        return "*".gettype($value)."*";
    }
    /**
     * get key value , check exist key no exist expection return. if not exist key, trow Exception
     * @param  mixed $value 
     * @param  $array
     * @return mixed
     */
    public static function getValueArray($key, $array) {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }
        throw new \Exception('Not Found key') ;
    }

} // end of class
