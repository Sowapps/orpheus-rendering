<?php
use Orpheus\Hook\Hook;

/* Loader File for the rendering sources
 */

if( !defined('ORPHEUSPATH') ) {
	// Do not load in a non-orpheus environment
	return;
}

// addAutoload('Rendering',		'rendering/Rendering');
// addAutoload('HTMLRendering',	'rendering/HTMLRendering');
// addAutoload('RawRendering',		'rendering/RawRendering');

define('HOOK_MENUITEMACCESS', 'menuItemAccess');
/* Hook HOOK_MENUITEMACCESS
 * Determine access in a menu item for a module
 * 
 * Parameters :
 * - boolean $access True to display menu item. Default: true.
 * - string $module The module name
*/
Hook::create(HOOK_MENUITEMACCESS);
