<?php
/**
 * Loader for orpheus Rendering library
 * 
 * Declare some hooks
 */

use Orpheus\Hook\Hook;

if( !defined('ORPHEUSPATH') ) {
	// Do not load in a non-orpheus environment
	return;
}

define('HOOK_MENUITEMACCESS', 'menuItemAccess');
/*
 * Hook HOOK_MENUITEMACCESS
 * Determine access in a menu item for a module
 * 
 * Parameters :
 * - boolean $access True to display menu item. Default: true.
 * - string $module The module name
*/
Hook::create(HOOK_MENUITEMACCESS);
