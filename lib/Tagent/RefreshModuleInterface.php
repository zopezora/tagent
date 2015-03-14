<?php
/**
 * RefreshModule interface, part of Tagent
 */
namespace Tagent;
/**
 * onRefresh for Module classes.
 * @package Tagent
 */
interface RefreshModuleInterface
{
    /**
     * if open tag with attributes refresh='yes' , this method is called. 
     * @param  array $params 
     */
    public function onRefresh(array $params);
}
