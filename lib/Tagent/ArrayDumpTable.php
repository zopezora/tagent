<?php
namespace Tagent;

class ArrayDumpTable {

    protected $output = "";

    public function __construct($var)
    {
        $cssclass = "class='arraydumptable'";
        $this->output  = "<div".$cssclass.">\n";
        $this->output .= $this->expand($var);
        $this->output .= "</div>\n";
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
        $output = "<table border=1>\n";
        $output .= " <tr>\n";
        $output .= "  <th colspan=2>array</th>\n";
        $output .= " </tr>\n";
        foreach ($var as $key=>$value ){
            $output .= "<tr>\n";
            $output .= " <td>".htmlspecialchars($key)."</td>\n";
            $output .= " <td>".$this->expand($value)."</td>\n";
            $output .= "</tr>\n";
        }
        $output .= "</table>\n";
        return $output;
    }
    public function objectExpand($var)
    {
        $output = "<table border=1>\n";
        $output .= " <tr>\n";
        $output .= "  <th colspan=2>object</th>\n";
        $output .= " </tr>\n";

            $output .= "<tr>\n";
            $output .= " <td colspan=2>".print_r($key, true)."</td>\n";
            $output .= "</tr>\n";

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