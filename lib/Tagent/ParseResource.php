<?php
/**
 * Resource container for parse, part of Tagent
 * @package Tagent
 */
namespace Tagent;

class ParseResource {
    // const pattern
    const OUTPUT_FORMATS  = 'h|r|u|j|b|html|raw|url|json|base64';
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
     * @var string
     */ 
    public $loopkey = '';

    /**
     * @var object Buffer
     */
    public $buffer = null;
    /**
     * constructor
     * @return void
     */
    public function __construct(Buffer $buffer)
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
     * variable fetch .  search {@scope:name|format} , deployment to the value
     * @param  string $source
     * @param  bool   $return  .true...return string, false...buffering   
     * @return string|void
     */
    public function varFetch($source, $return = false)
    {
        $agent = Agent::self();
        $output = '';
        $pattern = "/{@(?|(".self::VARIABLE_SCOPES."):|())((?>\w+))(?|((?:\[(?:(?>[^\[\]]+)|(?R))\])+)|())(?|((?:\|(?:".self::OUTPUT_FORMATS."))+)|())}/i";

        while (preg_match($pattern, $source, $matches, PREG_OFFSET_CAPTURE)) {
            $match = $matches[0][0];
            $pos   = $matches[0][1];
            $len   = strlen($match);

            $scope   = $matches[1][0];
            $key     = $matches[2][0];
            $index   = $matches[3][0];

            $format = ($matches[4][0]) ? substr($matches[4][0], 1) : '';
            $formats = explode('|', $format);

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
                $this->buffer(substr($source, 0, $pos));
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
                //format
                foreach ($formats as $format){
                    $var = $this->format($var, $format);
                }
            } else {
                $agent->log(E_PARSE,'Not Found Variable  '.$match, true, $this->module);
                $var = $match;
            }
            if ($return) {
                $output .= $var;
            } else {
                $this->buffer($var);
            }
            // remaining non-match string 
            $source = substr($source, $pos + $len);

        } // end of while

        if ($return) {
            return $output.$source;
        } else {
            $this->buffer($source);
            $agent->line += substr_count($source,"\n");
        }
    }
    /**
     * convert format 
     * @param  mixed  $source  string|array
     * @param  string $format 
     * @return string|false
     */
    public function format($source, $format = 'h')
    {
        $agent = Agent::self();
        if ($source === false) {
            return false;
        }
        if (is_object($source) && ! method_exists($source,'__toString')) {
            $agent->log(E_PARSE,'Cannot convert from object ('.get_class($source).') to string ');
            if ($agent->debug()) {
                return "*Object*";
            }
            return false;
        }
        $format = ($format=="") ? "h" : strtolower($format)[0];
        switch ($format) {
            case 'h':
                $output = htmlspecialchars((string) $source, ENT_QUOTES, 'UTF-8');
                break;
            case 'r':
                $output = (string) $source;
                break;
            case 'u':
                $output = urlencode((string) $source);
                break;
            case 'j':
                $output = json_encode($source);
                break;
            case 'b':
                $output = base64_encode($source);
                break;
            default:
                $agent->log(E_WARNING, "Unvalid format (".$format.")");
                $this->format($source, 'h'); // retry
        }
        return $output;
    }

}
