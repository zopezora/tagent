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
    // --- Module Variable --------------------------------- 
    /**
     * get module variable
     * @param  string $key 
     * @return mixed
     */
    final public function getVariable($key = null, $modulename = null)
    {
        if (! isset($modulename)) {
            $modulename = Agent::self()->getModuleNameByClass(get_class($this));
        }
        return Agent::self()->getVariable($key, $modulename);
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
            $modulename = Agent::self()->getModuleNameByClass(get_class($this));
        }
        Agent::self()->setVariable($key, $value, $modulename);
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
            $modulename = Agent::self()->getModuleNameByClass(get_class($this));
        }
         Agent::self()->setVariablesByArray($array, $modulename);
    }

    // ---- Module object locator -----------------------------
    /**
     * get
     * @param  string $name 
     * @param  string $modulename 
     * @return object|null
     */
    final public function get($name , $modulename = null)
    {
        if (! isset($modulename)){
            $modulename = Agent::self()->getModuleNameByClass(get_class($this));
        }
        return Agent::self()->get($name, $modulename);
    }
    /**
     * set object
     * @param string $name 
     * @param object $object   if null , unset Object
     * @param string $modulename
     * @return void
     */
    final public function set($name, $object, $modulename = null)
    {
        if (! isset($modulename)){
            $modulename = Agent::self()->getModuleNameByClass(get_class($this));
        }
        Agent::self()->set($name, $object, $modulename);
    }
    /**
     * has object
     * @param string $name 
     * @param string $modulename
     * @return bool  true|false
     */
    final public function has($name, $modulename = null)
    {
        if (! isset($modulename)){
            $modulename = Agent::self()->getModuleNameByClass(get_class($this));
        }
        return Agent::self()->has($name, $modulename);
    }
    /**
     * log
     * @param  integer|string $level 
     * @param  string $message
     * @return bool  true|false
     */
    final public function log($level, $message, $escape = true)
    {
        $agent = Agent::self();
        if ($agent->debug()) {
            $modulename = $agent->getModuleNameByClass(get_class($this));
            $agent->log($level, $message, $escape, $modulename);
        }
    }

}
