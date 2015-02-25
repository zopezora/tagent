<?php
namespace Tagent;

class Attribute
{
    // const pattern
    const RESERVED_ATTRS  = 'Module|Pull|Loop|Read|Trim|Parse|Close|Check|Debug|Store|Header|Reopen|Restore|Refresh|Template';
    /**
     * @var array
     */
    public $reserved = array(
                "Module"        => null,
                "Pull"          => null,
                "Loop"          => null,
                "Read"          => null,
                "Trim"          => null,
                "Parse"         => null,
                "Close"         => null,
                "Check"         => null,
                "Debug"         => null,
                "Store"         => null,
                "Header"        => null,
                "Reopen"        => null,
                "Restore"       => null,
                "Refresh"       => null,
                "Template"      => null,
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
        $agent = Agent::self();
        $pattern = "/(?:[^'\"\s]+|\"(?:\\\\\"|[^\"])*\"|'(?:\\\\'|[^'])*')+/";
        if (preg_match_all( $pattern,$source,$matches)) {
            $array = $matches[0];
            $valid_pattern    = "/(?|(\w+)|(\[\w+\]))=([^'\"\s]+|\"(?:\\\\\"|[^\"])*\"|'(?:\\\\'|[^'])*')/";

            $reserved_pattern = "/^(".self::RESERVED_ATTRS.")$/";
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
                        $value = $resource->varFetch($value, true);
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