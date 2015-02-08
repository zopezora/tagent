<?php
namespace Tagent;

class ArrayDumpTable {

    const CSS_CLASS     = 'arrayDump';
    const CSS_CLASS_KEY = 'arrayDumpKey';

    const STYLE = <<<'STYLE'
<style>
    table.arrayDump {
        border-collapse : collapse;
        margin          : 1px;
    }
    table.arrayDump th {
        text-align : center ;
        background-color: #bbb;
        border: 1px solid #aaa;
    }
    table.arrayDump td {
        background-color : #fff;
        border           : 1px solid #aaa;
        padding : 2px 5px;
    }
    table.arrayDump td.arrayDumpKey {
        background      : #eee;
    }
</style>

STYLE;

    protected $output = "";


    public function __construct($var, $title = '')
    {
        $this->output .= $this->expand($var);
    }
    public function __toString()
    {
        return $this->output;
    }
    public function expand($var)
    {
        switch(gettype($var)) {
            case "array":
                $output = $this->arrayExpand($var);
                break;
            case "object":
                $output =$this->objectExpand($var);
                break;
            case "resource":
                $output =$this->resourceExpand($var);
                break;
            // scalar
            case "boolean":
                $output = ($var) ? "TRUE" : "FALSE";
                break;
            case "NULL":
                $output = "NULL";
                break;
            default:
                $output = htmlspecialchars($var);
                break;
        }
        return $output;
    }
    public function arrayExpand($var)
    {
        $output = "<table class='".self::CSS_CLASS."'>\n";
        $output .= " <tr>\n";
        $output .= "  <th colspan=2>array</th>\n";
        $output .= " </tr>\n";
        if (empty($var)){
            $output .= " <tr>\n";
            $output .= "  <td colspan=2>[empty]</td>\n";
            $output .= " </tr>\n";
        }
        foreach ($var as $key=>$value ){
            $output .= " <tr>\n";
            $output .= "  <td class=".self::CSS_CLASS_KEY.">".htmlspecialchars($key)."</td>\n";
            $output .= "  <td>".$this->expand($value)."</td>\n";
            $output .= " </tr>\n";
        }
        $output .= "</table>\n";
        return $output;
    }
    public function objectExpand($var)
    {
        $output = "<table border=1>\n";
        $output .= " <tr>\n";
        $output .= "  <th colspan=2>".get_class($var)."</th>\n";
        $output .= " </tr>\n";

        if ($var instanceof \Traversable) {
            foreach ($var as $key=>$value ){
                $output .= " <tr>\n";
                $output .= "  <td class=".self::CSS_CLASS_KEY.">".htmlspecialchars($key)."</td>\n";
                $output .= "  <td>".$this->expand($value)."</td>\n";
                $output .= " </tr>\n";
            }
        } else {
            $output .= "<tr>\n";
            $output .= " <td colspan=2>".print_r($var, true)."</td>\n";
            $output .= "</tr>\n";
        }

        $output .= "</table>\n";
        return $output;
    }
    public function resourceExpand($var)
    {
        $output = "<table border=1>\n";
        $output .= " <tr>\n";
        $output .= "  <th colspan=2>resource</th>\n";
        $output .= " </tr>\n";

            $output .= " <tr>\n";
            $output .= "  <td colspan=2>".get_resource_type($key)."</td>\n";
            $output .= " </tr>\n";

        $output .= "</table>\n";
        return $output;
    }

}