<?php
/**
 * refresh module interface, part of Tagent
 * for Module classes.
 * @package Tagent
 */
namespace Tagent;

interface RefreshModuleInterface {
    /**
     * if open tag with attributes refresh='yes' , this method is called. 
     * @param  array $params 
     * @return void
     */
    public function refresh(array $params);
}
