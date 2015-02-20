<?php
/**
 * Expand variable, part of Tagent
 * @package Tagent
 */
namespace Tagent;

class ExpandVariable
{
    // const for css
    const CSS_CLASS          = 'expandTable';
    const CSS_CLASS_KEY      = 'expandTableKey';
    const CSS_CLASS_NOTE     = 'expandTableNote';
    const CSS_CLASS_PROPERTY = 'expandTableProperty';

    const STYLE = <<<'STYLE'
<style>
    table.expandTable {
        border-collapse : collapse;
        margin          : 1px;
    }
    table.expandTable th {
        text-align : center ;
        background-color: #bbb;
        border: 1px solid #aaa;
    }
    table.expandTable td {
        background-color : #fff;
        border           : 1px solid #aaa;
        padding : 2px 5px;
    }
    table.expandTable td.expandTableKey {
        background      : #eee;
    }
    table.expandTable td.expandTableNote {
        background      : #f99;
    }
    table.expandTable td.expandTableProperty {
        background      : #7d7;
    }
</style>
STYLE;

    public static function expand($var)
    {
        switch(gettype($var)) {
            case "array":
                $output = self::expandArray($var);
                break;
            case "object":
                $output = self::expandObject($var);
                break;
            case "resource":
                $output = self::expandResource($var);
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
    public static function expandArray($var)
    {
        $output = "<table class='".self::CSS_CLASS."'>\n";
        $output .= " <tr>\n";
        $output .= "  <th colspan=2>array</th>\n";
        $output .= " </tr>\n";
        if (empty($var)) {
            $output .= " <tr>\n";
            $output .= "  <td colspan=2>[empty]</td>\n";
            $output .= " </tr>\n";
        }
        foreach ($var as $key=>$value ) {
            $output .= " <tr>\n";
            $output .= "  <td class=".self::CSS_CLASS_KEY.">".htmlspecialchars($key)."</td>\n";
            $output .= "  <td>".self::expand($value)."</td>\n";
            $output .= " </tr>\n";
        }
        $output .= "</table>\n";
        return $output;
    }
    public static function expandObject($var)
    {
        $output = "<table class='".self::CSS_CLASS."'>\n";
        $output .= " <tr>\n";
        $output .= "  <th colspan=2>".get_class($var)."</th>\n";
        $output .= " </tr>\n";

        $properties = get_object_vars($var);
        $output .= "<tr><td colspan=2 class=".self::CSS_CLASS_PROPERTY.">properties</td></tr>";
        if (empty($properties)) {
            $output .= "<tr><td colspan=2>[empty]</td></tr>";
        }
        foreach ($properties as $key=>$value ) {
            $output .= " <tr>\n";
            $output .= "  <td class='".self::CSS_CLASS_KEY."'>".htmlspecialchars($key)."</td>\n";
            $output .= "  <td>".self::expand($value)."</td>\n";
            $output .= " </tr>\n";
        }
        // trabasable
        if ($var instanceof \Traversable) {
            $output .= "<tr><td colspan=2 class=".self::CSS_CLASS_PROPERTY.">Trasable</td></tr>";
            foreach ($var as $key => $value ) {
                $output .= " <tr>\n";
                $output .= "  <td class='".self::CSS_CLASS_KEY."'>".htmlspecialchars($key)."</td>\n";
                $output .= "  <td>".self::expand($value)."</td>\n";
                $output .= " </tr>\n";
            }
            if (! $var instanceof \itelator && ! $var instanceof \ArrayAccess) {
                $output .= "<tr><td colspan=2 class=".self::CSS_CLASS_NOTE.">Non-itelator object<br> may not be able to rewind.</td></tr>";
            }
        } else {
            $output .= "<tr>\n";
            $output .= " <td colspan=2>Non taraserble</td>\n";
            $output .= "</tr>\n";
        }
        $output .= "</table>\n";
        return $output;
    }
    public static function expandResource($var)
    {
        $output = "<table class='".self::CSS_CLASS."'>\n";
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