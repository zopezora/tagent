<?php
namespace Tagent;

class Attribute
{
    // const pattern
    const RESERVED_ATTRS  = 'module|method|loop|parse|close|refresh|newmodule|template|check|debug';
    /**
     * @var array
     */
    public $reserved = array(
                "module"        => null,
                "method"        => null,
                "loop"          => null,
                "parse"         => null,
                "close"         => null,
                "refresh"       => null,
                "newmodule"     => null,
                "template"      => null,
                "check"         => null,
                "debug"         => null,
    );
    /**
     * @var array
     */
    public $appends = array();
    /**
     * @var array
     */
    public $params = array();
    /**
     * construct
     * @param  string $source 
     * @param  object ParseResource $resource 
     * @return void
     */
    public function __construct($source, ParseResource $resource)
    {
        $agent = Agent::getInstance();
        $pattern = "/(?:\"[^\"]*\"|'[^']*'|[^'\"\s]+)+/";
        if (preg_match_all( $pattern,$source,$matches)) {
            $array = $matches[0];
            $valid_pattern    = "/(?|(\w+)|(\[\w+\]))=(\"[^\"]*\"|'[^']*'|[^'\"\s]+)/";
            $reserved_pattern = "/^(".self::RESERVED_ATTRS.")$/i";
            $varkey_pattern   = "/^\[(\w+)\]$/i";
            foreach($array as $v) {
                // valid attribute
                if (preg_match($valid_pattern, $v, $sp_match)){
                    $key   = $sp_match[1];   // foo or [foo]
                    $value = $sp_match[2];   // 'bar' or {@name}
                    // sepalate reserved attribute
                    $parentkey = 'params';
                    if (preg_match($reserved_pattern, $key, $attr_match)){
                        $parentkey = 'reserved';
                        $key = strtolower($key);
                    } else {
                        if (preg_match($varkey_pattern, $key, $varkey_match)) {
                            $parentkey = 'appends';
                            $key = $varkey_match[1];
                        }
                    }
                    if (($ret = Utility::removeQuote($value)) !== false) {
                        $value = $ret;
                    } else {
                        // un quate value, try for fetch {@VARIABLE}
                        $value = $resource->varFetch($value);
                    }
                    $this->{$parentkey}[$key] = $value;
                } else {
                    // Unvalid attribute
                    $agent->log(E_WARNING, "Unvalid attribute (".$v.")", true, $resource->module);
                }
            } // end of foreach
        }
    }
}