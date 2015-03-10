<?php
/**
 * Resource container for parse, part of Tagent
 * @package Tagent
 */
namespace Tagent;

class ParseResource {
    // const pattern
    const VARIABLE_SCOPES = 'm|l|g|module|loop|global';
    /**
     * @var string
     */
    public $module = 'GLOBAL';
    /**
     * @var array
     */
    public $pullVars = array();
    /**
     * @var array
     */
    public $loopVars = array();
    /**
     * @var array
     */
    public $inLoopVarsList = array();
    /**
     * @var string
     */ 
    public $loopkey = '';
    /**
     * @var string
     */ 
    public $inTag = '';
    /**
     * @var integer
     */
    public $trimLineTag = 0;
    /**
     * @var object Buffer
     */
    public $buffer = null;
    /**
     * @var bool
     */
    public $forceClose = false;
    /**
     * @var bool 
     */
    public $parse = true;
    /**
     * constructor
     * @return void
     */
    public function __construct(ParseResource $resource = null)
    {
        if (isset($resource)) {
            $this->buffer = $resource->buffer;
            $this->module = $resource->module;
            $this->pullVars = $resource->pullVars;
            $this->inLoopVarsList = array('_NOLOOP_'=>$resource->loopVars);
        } else {
            $agent = Agent::self();
            $this->buffer = $agent->createBuffer();
        }
    }
    /**
     * set Buffer object
     * @param object $buffer 
     * @return void
     */
    public function setBuffer($buffer)
    {
        $this->buffer = $buffer;
    }
    /**
     * buffer
     * @param  string $str 
     * @return string
     */
    public function buffer($str)
    {
        return $this->buffer->buffer($str);
    }
    /**
     * variable fetch .  search {@scope:name|filter} , deployment to the value
     * @param  string $source
     * @param  bool   $return  .true...return string, false...buffering   
     * @return string|void
     */
    public function varFetch($source, $return = false)
    {
        $agent = Agent::self();
        $filterPattern = $agent->filterManager->pattern;

        $output = '';
        $pattern = "/{@(?|(".self::VARIABLE_SCOPES."):|())((?>\w+))(?|((?:\[(?:(?>[^\[\]]+)|(?R))\])+)|())(?|((?:\|(?:".$filterPattern."))+)|())}/i";

        while (preg_match($pattern, $source, $matches, PREG_OFFSET_CAPTURE)) {
            $match = $matches[0][0];
            $pos   = $matches[0][1];
            $len   = strlen($match);

            $scope   = $matches[1][0];
            $key     = $matches[2][0];
            $index   = $matches[3][0];

            $key_array = array($key);
            if (preg_match_all("/\[((?:(?>[^\[\]]+)|(?R))*)\]/", $index, $index_matches)) {
                foreach ($index_matches[1] as $im) {
                    $key_array[] = $this->varFetch($im, true);
                }
            }
            // Before the string of match
            if ($return) {
                $output .= substr($source, 0, $pos);
            } else {
                $this->buffer->buffer(substr($source, 0, $pos));
                $agent->line += substr_count(substr($source, 0, $pos), "\n");
            }
            // --- parse variable priority ---
            //  1.pullVars   2.$loopVars   3.moduleVars   4.globalmoduleVars
            $scope = ($scope == "") ? "*" : strtoupper($scope[0]);

            $var = null;
            switch ($scope) {
                case "*":
                    $var = Utility::getValueByDeepkey($key_array, $this->pullVars);
                    if (isset($var)){
                        break;
                    } // else no break
                case "L":
                    if ($key == 'LOOPKEY') {
                        $var = $this->loopkey;
                    } else {
                        $var = Utility::getValueByDeepkey($key_array, $this->loopVars);
                    }
                    if (isset($var) || $scope == "L") { 
                        break;
                    } // else no break
                case "M": 
                    $var = Utility::getValueByDeepkey($key_array, $agent->getVariable(null, $this->module));
                    if (isset($var) || $scope == "M" || $this->module == 'GLOBAL') {
                        break;
                    } // else no break
                case "G":
                    $var = Utility::getValueByDeepkey($key_array, $agent->getVariable(null, 'GLOBAL'));
                    break;
            }
            if (isset($var)) {
                //filter
                if ($matches[4][0]) {
                    preg_match_all("/\|(".$filterPattern.")/", $matches[4][0], $filterMatch);
                    $filters = $filterMatch[1];
                } else {
                    $filters = array('h');
                }
                foreach ($filters as $filter){
                    $var = $agent->filterManager->filter($var, $filter);
                }
            } else {
                $agent->log(E_PARSE,'Not Found Variable  '.$match, true, $this->module);
                $var = $match;
            }
            if ($return) {
                $output .= $var;
            } else {
                $this->buffer->buffer($var);
            }
            // remaining non-match string 
            $source = substr($source, $pos + $len);

        } // end of while

        if ($return) {
            return $output.$source;
        } else {
            $this->buffer->buffer($source);
            $agent->line += substr_count($source,"\n");
        }
    }

}
