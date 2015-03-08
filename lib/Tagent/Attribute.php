<?php
namespace Tagent;

class Attribute
{
    // const pattern
    const RESERVED_ATTRS  = 'Module|Pull|Loop|Read|Trim|Parse|Close|Check|Debug|Store|Header|Reopen|Restore|Refresh|Template';
    /**
     * @var array
     */
    public $reserved = array();

    // public $reservedQueue = null;
    // protected $priorityTable = array(
    //             "Debug"         => 15,
    //             "Header"        => 14,
    //             "Store"         => 13,
    //             "Module"        => 12,
    //             "Reopen"        => 11,
    //             "Refresh"       => 10,
    //             "Pull"          => 9,
    //             "Loop"          => 8,
    //             "Template"      => 7,
    //             "Read"          => 6,
    //             "Restore"       => 5,
    //             "Trim"          => 4,
    //             "Check"         => 3,
    //             "Parse"         => 2,
    //             "Close"         => 1,
    // );
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
//        $this->reservedQueue = new \SplPriorityQueue;
        if (preg_match_all("/(?:[^'\"\s]+|\"(?:\\\\\"|[^\"])*\"|'(?:\\\\'|[^'])*')+/", $source, $matches)) {
            $array = $matches[0];
            $valid_pattern    = "/(?|(\w+)|(\[\w+\]))=([^'\"\s]+|\"(?:\\\\\"|[^\"])*\"|'(?:\\\\'|[^'])*')/";

            $reserved_pattern = "/^(".self::RESERVED_ATTRS.")$/";
            $varkey_pattern   = "/^\[(\w+)\]$/i";
            foreach($array as $v) {
                // valid attribute
                if (preg_match($valid_pattern, $v, $sp_match)){
                    $key   = $sp_match[1];   // foo or [foo]
                    $value = $sp_match[2];   // 'bar' or {@name}

                    if (($ret = Utility::removeQuote($value)) !== false) {
                        $value = $ret;
                    } else {
                        // un quate value, try for fetch {@VARIABLE}
                        $value = $resource->varFetch($value, true);
                    }
                    // sepalate reserved attribute
                    $parentkey = 'params';
                    if (preg_match($reserved_pattern, $key, $attr_match)){
                        $this->reserved[]= array($key, $value);
//                        $this->reservedQueue->insert(array($key, $value), $this->priorityTable[$key]);
                    } else {
                        if (preg_match($varkey_pattern, $key, $varkey_match)) {
                            $parentkey = 'appends';
                            $key = $varkey_match[1];
                        }
                        $this->{$parentkey}[$key] = $value;
                    }
                } else {
                    // Unvalid attribute
                    $agent = Agent::self();
                    $agent->log(E_WARNING, "Unvalid attribute (".$v.")", true, $resource->module);
                }
            } // end of foreach
        }
    }
}