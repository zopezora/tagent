<?php
/**
 * Logger class, part of Tagent
 */
namespace Tagent;
/**
 * Log container and logging
 * @package Tagent
 */
class Logger
{
    // const for css
    const CSS_CLASS = 'tagentLog';
    const CSS_CLASS_ERROR   = 'tagentLogError';
    const CSS_CLASS_WARNING = 'tagentLogWarning';
    const CSS_CLASS_PARSE   = 'tagentLogParse';
    const CSS_CLASS_NOTICE  = 'tagentLogNotice';
    const CSS_CLASS_CHECK   = 'tagentLogCheck';
    const CSS_CLASS_USER    = 'tagentLogUser';
    const CSS_CLASS_HELP    = 'tagentLogHelp';

    const STYLE = <<<'STYLE'
<style>
    div.tagentLog {
        border: 1px solid #888;
        font-size : 100%;
        margin : 0;
        padding: 0;
    }

    table.tagentLog {
        border-collapse : collapse;
        margin          : 3px;
    }
    table.tagentLog th {
        background-color : #bbb;
        border           : 1px solid #888;
    }
    table.tagentLog td {
        border  : 1px solid #888;
        padding : 2px;
    }

    table.tagentLog tr.tagentLogError {
        background-color : #f77;
    }
    table.tagentLog tr.tagentLogWarning {
        background-color : #fc7;
    }
    table.tagentLog tr.tagentLogParse {
        background-color : #fdd;
    }
    table.tagentLog tr.tagentLogNotice {
        background-color : #cef;
    }
    table.tagentLog tr.tagentLogCheck {
        background-color : #ffc;
    }
    table.tagentLog tr.tagentLogUser {
        background-color : #cfc;
    }
    table.tagentLog tr.tagentLogHelp {
        background-color : #8ea;
    }
    table.tagentLog td ul {
        margin  : 0 0 0 1em;
        padding : 0px;
    }
    table.tagentLog td li {
        margin : 5px 0
    }

</style>

STYLE;

    /**
     * @var array log entry container
     */
    protected $logs = array();
    /**
     * @var array preset log level
     */
    protected $loglevel = array(
                                E_ERROR      => 'ERROR',     // 1
                                E_WARNING    => 'WARNING',   // 2
                                E_PARSE      => 'PARSE',     // 4
                                E_NOTICE     => 'NOTICE',    // 8
                                E_DEPRECATED => 'CHECK',     // 8192
                                E_STRICT     => 'HELP'       // 2048
                               );
    /**
     * write log
     * @param  integer|string $level
     * @param  string $message
     * @param  bool   $escape   if true , when reporting, output using htmlspecialchar(). if false, raw output.
     * @param  string $module 
     * @return void
     */
    public function log($level, $message, $escape = true, $module = "")
    {
        $agent = Agent::self();
        if (! $agent->debug()) {
            return false;
        }
        // $level 1.ERROR 2.WARNING 4.PARSE 8.NOTICE 8192 (CHECK)E_DEPRECATED 
        //        etc code / string... 1024 E_USER_NOTICE
        if (is_string($level)) {
            $level_str = (is_string($level)) ? $level: $level;
            $level     = E_USER_NOTICE ; // 0x4000
        } else {
            if (isset($this->loglevel[$level])) {
                $level_str = $this->loglevel[$level];
            } else {
                $level_str = "UNDEFINED";
                $level     = E_USER_NOTICE ; // 0x4000
            }
        }
        $offset = ($agent->getLine() !=0 ) ? $agent->getConfig('line_offset') : 0;

        $this->logs[] = array(
                    "line"      => $agent->getLine() + $offset,
                    "level"     => $level,
                    "level_str" => $level_str,
                    "module"    => $module,
                    "message"   => $message,
                    "escape"    => $escape,
        );
    }
    /**
     * report log
     * @return string
     */
    public function report()
    {
        $agent = Agent::self();
        $level  = $agent->log_reporting();

        $output  = "<div class='".self::CSS_CLASS."'>\n";
        $checkflag = false;

        if (empty($this->logs)) {
            $output .= "No Report\n";
        } else {
            $output .= "<table class='".self::CSS_CLASS."'>\n";
            $output .= " <tr>\n";
            $output .= "  <th>LINE</th>";
            $output .= "  <th>LEVEL</th>";
            $output .= "  <th>MODULE</th>";
            $output .= "  <th>MESSAGE</th>";
            $output .= " </tr>\n";
            foreach($this->logs as $log) {
                if ($log['level'] & $agent->log_reporting()) {
                    if (isset($this->loglevel[$log['level']])) {
                        $cssclass = constant("self::CSS_CLASS_{$this->loglevel[$log['level']]}");
                        if ($log['level'] == E_DEPRECATED) {
                            $checkflag = true;
                        }
                        if ($log['level'] == E_STRICT) {
                            $helpflag = true;
                        }
                    } else {
                        $cssclass = self::CSS_CLASS_USER;
                    }
                    $output .= " <tr class='".$cssclass."'>\n";
                    $output .= "  <td>".htmlspecialchars($log['line'])."</td>\n";
                    $output .= "  <td>".htmlspecialchars($log['level_str'])."</td>\n";
                    $output .= "  <td>".htmlspecialchars($log['module'])."</td>\n";
                    if ($log['escape']) {
                        $output .= "  <td>".nl2br(htmlspecialchars($log['message']))."</td>\n";
                    } else {
                        $output .= "  <td>".$log['message']."</td>\n";
                    }
                    $output .= " </tr>\n";
                }
            }
            $output .= "</table>\n";
        }
        $output .= "</div>\n";

        $output .= self::STYLE;
        if ($checkflag) {
            $output .= ExpandVariable::STYLE;
        }
        if ($helpflag) {
            $output .= Help::STYLE;
        }
        return $output;
    }

}