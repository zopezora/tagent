<?php
/**
 * Attribute class, part of Tagent
 */
namespace Tagent;
/**
 * tag parser, module control, Object locator
 * @package Tagent
 */
class Attribute
{
    // const pattern
    const RESERVED_PATTERN  = "/^(Module|Pull|Loop|Read|Trim|Parse|Close|Check|Debug|Store|Header|Reopen|Restore|Refresh|Template)$/";
    const ATTRIBUTE_PATTERN = "/(?:[^'\"\s]+|\"(?:\\\\\"|[^\"])*\"|'(?:\\\\'|[^'])*')+/";
    const VALID_PATTERN     = "/(?|(\w+)|(\[\w+\]))=((?:[^'\"\s]+|\"(?:\\\\\"|[^\"])*\"|'(?:\\\\'|[^'])*')+)/";
    /**
     * @var array Parsed result of reserved attributes.
     */
    public $reserved = array();
    /**
     * @var array Parsed result of variable attributes.
     */
    public $appends = array();
    /**
     * @var array Parsed result of params attributes.
     */
    public $params = array();
    /**
     * construct
     * @param  string $source 
     * @param  object $resource ParseResource object
     * @return void
     */
    public function __construct($source, ParseResource $resource)
    {
        if (preg_match_all(self::ATTRIBUTE_PATTERN, $source, $matches)) {
            foreach($matches[0] as &$v) {
                // valid attribute
                if (preg_match(self::VALID_PATTERN, $v, $sp_match)) {
                    $key   = $sp_match[1];   // foo or [foo]
                    $value = $sp_match[2];   // 'bar' or {@name}

                    if (($ret = Utility::removeQuote($value)) !== false) {
                        $value = $ret;
                    } else {
                        // un quate value, try for fetch {@VARIABLE}
                        $value = $resource->varFetch($value, true);
                    }
                    if ($key[0] == '[') {
                        $key = substr($key, 1 , -1);
                        $this->appends[$key] = $value;
                    } elseif (preg_match(self::RESERVED_PATTERN, $key, $attr_match)) {
                        $this->reserved[] = array($key, $value);
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