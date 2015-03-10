<?php
/**
 * Attribute, part of Tagent
 * tag parser, module control, Object locator
 * @package Tagent
 */
namespace Tagent;

class Attribute
{
    // const pattern
    const RESERVED_PATTERN  = "/^(Module|Pull|Loop|Read|Trim|Parse|Close|Check|Debug|Store|Header|Reopen|Restore|Refresh|Template)$/";
    const ATTRIBUTE_PATTERN = "/(?:[^'\"\s]+|\"(?:\\\\\"|[^\"])*\"|'(?:\\\\'|[^'])*')+/";
    const VARKEY_PATTERN    = "/^\[(\w+)\]$/";
    const VALID_PATTERN     = "/(?|(\w+)|(\[\w+\]))=([^'\"\s]+|\"(?:\\\\\"|[^\"])*\"|'(?:\\\\'|[^'])*')/";
    /**
     * @var array
     */
    public $reserved = array();
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
        if (preg_match_all(self::ATTRIBUTE_PATTERN, $source, $matches)) {
            foreach($matches[0] as &$v) {
                // valid attribute
                if (preg_match(self::VALID_PATTERN, $v, $sp_match)){
                    $key   = $sp_match[1];   // foo or [foo]
                    $value = $sp_match[2];   // 'bar' or {@name}

                    if (($ret = Utility::removeQuote($value)) !== false) {
                        $value = $ret;
                    } else {
                        // un quate value, try for fetch {@VARIABLE}
                        $value = $resource->varFetch($value, true);
                    }
                    // separate reserved appends params
                    if (preg_match(self::RESERVED_PATTERN, $key, $attr_match)){
                        $this->reserved[]= array($key, $value);
                    } elseif (preg_match(self::VARKEY_PATTERN, $key, $varkey_match)) {
                            $key = $varkey_match[1];
                            $this->appends[$key] = $value;
                    } else {
                            $this->params[$key] = $value;
                    }
                } else {
                    // Unvalid attribute
                    $agent = Agent::self();
                    $agent->log(E_WARNING, "Unvalid attribute (".$v.")", true, $resource->module);
                }
            } // end of foreach
        } // end of match_all
    }
}