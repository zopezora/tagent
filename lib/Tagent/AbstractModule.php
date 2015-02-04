<?php
/**
 * abstract Module, part of Tagent
 * access to module variable and object locator
 * for Module classes and classes belonging to a module
 * @abstract
 * @package Tagent
 */
namespace Tagent;

use Tagent\Agent;

abstract class AbstractModule
{
    /**
     * get module variable
     * @param  string $key 
     * @return mixed
     */
    final public function getVariable($key = null, $modulename = null)
    {
        if (! isset($modulename)) {
            $modulename = Agent::getInstance()->getModuleNameByObject($this);
        }
        return Agent::getInstance()->getVariable($key, $modulename);
    }
    /**
     * set module variable
     * @param string $key 
     * @param mixed  $value 
     * @return void
     */
    final public function setVariable($key, $value, $modulename = null)
    {
        if (! isset($modulename)) {
            $modulename = Agent::getInstance()->getModuleNameByObject($this);
        }
        Agent::getInstance()->setVariable($key, $value, $modulename);
    }

    /**
     * set module variable by array.  always override
     * @param  array  $array 
     * @param  string $modulename 
     * @return void
     */
    final public function setVariablesByArray(array $array, $modulename = null)
    {
        if (! isset($modulename)) {
            $modulename = Agent::getInstance()->getModuleNameByObject($this);
        }
        Agent::getInstance()->setVariablesByArray($array, $modulename);
    }

    // ---- object locator ----
    /**
     * get
     * @param string $name 
     * @param string $modulename 
     * @return object|null
     */
    public function get($name , $modulename = null)
    {
        if (! isset($modulename)){
            $modulename = Agent::getInstance()->getModuleNameByObject($this);
        }
        return Agent::getInstance()->get($name,$modulename);
    }
    /**
     * set object
     * @param string $name 
     * @param object $object   if null , unset Object
     * @param string $modulename
     * @return void
     */
    public function set($name, $object, $modulename = null)
    {
        if (! isset($modulename)){
            $modulename = Agent::getInstance()->getModuleNameByObject($this);
        }
        Agent::getInstance()->set($name, $object, $modulename);
    }
    /**
     * has object
     * @param string $name 
     * @param string $modulename
     * @return bool  true|false
     */
    public function has($name, $modulename = null)
    {
        if (! isset($modulename)){
            $modulename = Agent::getInstance()->getModuleNameByObject($this);
        }
        return Agent::getInstance()->has($name, $modulename);
    }

}
