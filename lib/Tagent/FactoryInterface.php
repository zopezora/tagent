<?php
/**
 * Factory Interface, part of Tagent
 */
 namespace Tagent;
/**
 * factory method for object locator 
 * @package Tagent
 */
interface FactoryInterface
{
    /**
     * factory
     * @return instance
     */
    public function factory();
}
