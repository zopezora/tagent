<?php
/**
 * CloseModule interface, part of Tagent
 */
namespace Tagent;
/**
 * onClose for Module classes.
 * @package Tagent
 */
interface CloseModuleInterface
{
    /**
     * close module
     */
    public function onClose();
}
