<?php
/**
 * Refresh module interface, part of Tagent
 * for Module classes.
 * @package Tagent
 */
namespace Tagent;

interface RefreshModuleInterface
{
    /**
     * if open tag with attributes refresh='yes' , this method is called. 
     * @param  array $params 
     */
    public function onRefresh(array $params);
}
