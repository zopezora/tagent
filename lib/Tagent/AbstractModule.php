<?php
/**
 * AbstractModule class, Tagent
 */
namespace Tagent;
/**
 * Access to module variable/object, and log
 * @abstract
 * @package Tagent
 */
abstract class AbstractModule
{
    // --- Module Variable --------------------------------- 
    /**
     * get module variable
     * @param  string $key
     * @param  string $modulename default null then set by namespace of this class.
     * @param  int    $bk         default 1, for log report backtrack.
     * @return mixed
     */
    final public function getVariable($key = null, $modulename = null, $bk = 1)
    {
        if (! isset($modulename)) {
            $modulename = Agent::self()->getModuleNameByClass(get_class($this));
        }
        return Agent::self()->getVariable($key, $modulename, $bk);
    }
    /**
     * set module variable
     * @param string $key 
     * @param mixed  $value 
     * @param  string $modulename default null then set by namespace of this class.
     * @param  int    $bk         default 1, for log report backtrack.
     * @return void
     */
    final public function setVariable($key, $value, $modulename = null, $bk = 1)
    {
        if (! isset($modulename)) {
            $modulename = Agent::self()->getModuleNameByClass(get_class($this));
        }
        Agent::self()->setVariable($key, $value, $modulename, $bk);
    }

    /**
     * set module variable by array.  always override
     * @param  array  $array 
     * @param  string $modulename default null then set by namespace of this class.
     * @param  int    $bk         default 1, for log report backtrack.
     * @return void
     */
    final public function setVariablesByArray(array $array, $modulename = null, $bk = 1)
    {
        if (! isset($modulename)) {
            $modulename = Agent::self()->getModuleNameByClass(get_class($this));
        }
         Agent::self()->setVariablesByArray($array, $modulename, $bk);
    }

    // ---- Module object locator -----------------------------
    /**
     * Get an object from object locator
     * @param  string $name 
     * @param  string $modulename default null then set by namespace of this class.
     * @param  int    $bk         default 1, for log report backtrack.
     * @return object|null
     */
    final public function get($name , $modulename = null, $bk = 1)
    {
        if (! isset($modulename)) {
            $modulename = Agent::self()->getModuleNameByClass(get_class($this));
        }
        return Agent::self()->get($name, $modulename, $bk);
    }
    /**
     * Set an object to the object locator.
     * @param string $name   Name for call (case-sensitive)
     * @param object $object Object that is set. If null, unset Object
     * @param  string $modulename Default set by namespace of this class.
     * @param  int    $bk         Default 1, for log report backtrack.
     * @return void
     */
    final public function set($name, $object, $modulename = null, $bk =1)
    {
        if (! isset($modulename)) {
            $modulename = Agent::self()->getModuleNameByClass(get_class($this));
        }
        Agent::self()->set($name, $object, $modulename, $bk);
    }
    /**
     * To verify that it exists a name to the object locator.
     * @param string $name 
     * @param  string $modulename default null then set by namespace of this class.
     * @param  int    $bk         default 1, for log report backtrack.
     * @return bool  true|false
     */
    final public function has($name, $modulename = null, $bk = 1)
    {
        if (! isset($modulename)) {
            $modulename = Agent::self()->getModuleNameByClass(get_class($this));
        }
        return Agent::self()->has($name, $modulename, $bk);
    }
    /**
     * Logging
     * @param  integer|string $level 
     * @param  string $message
     * @param  bool   $escape
     * @return void
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
