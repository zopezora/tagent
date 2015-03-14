<?php
/**
 * ParseResource class, part of Tagent
 */
namespace Tagent;
/**
 * Resource for parsing variable
 * @package Tagent
 */
class ParseResource
{
    // const pattern
    const VARIABLE_SCOPES = '[lmg]';
    /**
     * @var string current module name 
     */
    public $module = 'GLOBAL';
    /**
     * @var array pull variable array
     */
    public $pullVars = array();
    /**
     * @var array current loop variable array
     */
    public $loopVars = array();
    /**
     * @var array loop variable array
     */
    public $inLoopVarsList = array();
    /**
     * @var string loopkey
     */ 
    public $loopkey = '';
    /**
     * @var string string inside tag
     */ 
    public $inTag = '';
    /**
     * @var integer trimming line count
     */
    public $trimLineTag = 0;
    /**
     * @var object Buffer
     */
    public $buffer = null;
    /**
     * @var bool force cloase true|false
     */
    public $forceClose = false;
    /**
     * @var bool parse switch true|false
     */
    public $parse = true;
    /**
     * constructor
     * @param object $resource
     * @return void
     */
    public function __construct(ParseResource $resource = null)
    {
        if (isset($resource)) {
            $this->buffer = $resource->buffer;
            $this->module = $resource->module;
            $this->pullVars = $resource->pullVars;
            $this->inLoopVarsList = array($resource->loopkey => $resource->loopVars);
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

        $sDelim = ($return) ? '(': '{(';
        $eDelim = ($return) ? ')': ')}';

        $pattern = '/'.$sDelim.'@(?|('.self::VARIABLE_SCOPES.'):|())((?>\w+))(?|((?:\[(?:'.Utility::IN_QUOTE_PATTERN.'|(?>[^\[\]]+)|(?1))\])+)|())(?|((?:\|(?:'.$filterPattern.'))+)|())'.$eDelim.'/';

        while (preg_match($pattern, $source, $matches, PREG_OFFSET_CAPTURE)) {
            $match = $matches[0][0];
            $pos   = $matches[0][1];
            $len   = strlen($match);

            $scope   = $matches[2][0];
            $key     = $matches[3][0];
            $index   = $matches[4][0];
            $filterString  = $matches[5][0];

            $key_array = array($key);
            if ($index != '' && preg_match_all('/\[((?:'.Utility::IN_QUOTE_PATTERN.'|(?>[^\[\]]+)|(?R))+)\]/', $index, $index_matches)) {
                foreach ($index_matches[1] as $im) {
                    if (($ret = Utility::removeQuote($im)) !== false) {
                        $key_array[] = $ret;
                    } else {
                        // un quate value, try for fetch {@VARIABLE}
                        $key_array[] = $this->varFetch($im, true);
                    }
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

            $var = null;
            switch ($scope) {
                case '':
                    $var = Utility::getValueByDeepkey($key_array, $this->pullVars);
                    if (isset($var)) {
                        break;
                    } // else no break
                case 'l':
                    if ($key == 'LOOPKEY') {
                        $var = $this->loopkey;
                    } else {
                        $var = Utility::getValueByDeepkey($key_array, $this->loopVars);
                    }
                    if (isset($var) || $scope == "l") { 
                        break;
                    } // else no break
                case 'm':
                    $var = Utility::getValueByDeepkey($key_array, $agent->getVariable(null, $this->module));
                    if (isset($var) || $scope == "m" || $this->module == 'GLOBAL') {
                        break;
                    } // else no break
                case 'g':
                    $var = Utility::getValueByDeepkey($key_array, $agent->getVariable(null, 'GLOBAL'));
                    break;
            }
            if (isset($var)) {
                //filter
                if ($filterString) {
                    preg_match_all("/\|(".$filterPattern.")/", $filterString, $filterMatch);
                    $filters = $filterMatch[1];
                } else {
                    $filters = ($return) ? array(): array('h');
                }
                foreach ($filters as $filter) {
                    $var = $agent->filterManager->filter($var, $filter);
                }
            } else {
                $agent->log(E_PARSE, 'Not Found Variable  '.$match, true, $this->module);
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
            $agent->line += substr_count($source, "\n");
        }
    }

}
