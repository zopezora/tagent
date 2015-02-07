<?php
namespace Tagent;

class Logger {

    const LOG_CHECK = 'CHECK';

    protected $logs = array();

    protected $loglevel = array(
                                E_ERROR      => 'ERROR',     // 1
                                E_WARNING    => 'WARNING',   // 2
                                E_PARSE      => 'PARSE',     // 4
                                E_NOTICE     => 'NOTICE',    // 8
                               );
        /**
     * log
     * @param  integer|string $level
     * @param  string $message
     * @param  string $module 
     * @return void
     */
    public function log($level, $message, $escape = false, $module = "")
    {

        $agent = Agent::getInstance();
        if (! $agent->debug()){
            return false;
        }
        // $level 1.ERROR 2.WARNING 4.PARSE 8.NOTICE     16384 E_USER_DEPRECATED
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

    public function report()
    {
        $agent = Agent::getInstance();
        $level  = $agent->log_reporting();

        $output  = "<div style='clear:both; border:1px solid #F00;'>\n";
        $checkflag = false;

        if (empty($this->logs)) {
            $output .= "No Report\n";
        } else {
            $output .= "<table border=1>\n";
            $output .= " <tr>\n";
            $output .= "  <th>LINE</th>";
            $output .= "  <th>LEVEL</th>";
            $output .= "  <th>MODULE</th>";
            $output .= "  <th>MESSAGE</th>";
            $output .= " </tr>\n";
            foreach($this->logs as $log){
                if ($log['level'] & $agent->log_reporting()) {
                    $output .= " <tr>\n";
                    $output .= "  <td>".htmlspecialchars($log['line'])."</td>\n";
                    $output .= "  <td>".htmlspecialchars($log['level_str'])."</td>\n";
                    $output .= "  <td>".htmlspecialchars($log['module'])."</td>\n";
                    if ($log['escape']) {
                        $output .= "  <td>".nl2br(htmlspecialchars($log['message']))."</td>\n";
                    } else {
                        $checkflag = true;
                        $output .= "  <td>".$log['message']."</td>\n";
                    }
                    $output .= " </tr>\n";
                }
            }
            $output .= "</table>\n";
            if ($checkflag) {
                // $output .= "stylesheet"  // todo
            }
        }
        $output .= "</div>\n";
        return $output;
    }

}