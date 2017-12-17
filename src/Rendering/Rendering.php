<?php
/**
 * Rendering
 */

namespace Orpheus\Rendering;

use Orpheus\Config\Config;
use Orpheus\Config\IniConfig;
use Orpheus\Core\Route;
use Orpheus\Rendering\Menu\MenuItem;

/**
 * The rendering class
 *
 * This class is the core for custom rendering use.
 */
abstract class Rendering {
	
	/**
	 * The default model to show
	 *
	 * @var string
	 */
	protected static $SHOWMODEL = 'show';
	
	/**
	 * The current Rendering
	 *
	 * @var Rendering
	 */
	protected static $rendering;
	
	/**
	 * The configuration of the menu
	 *
	 * @var array
	 */
	protected static $menusConf;
	
	/**
	 * The current rendering stack
	 *
	 * @var array
	 */
	protected $renderingStack = array();
	
	/**
	 * The current global rendering
	 *
	 * @var Rendering
	 */
	protected static $current;
	
	/**
	 * Push rendering to stack
	 *
	 * @param string $layout
	 * @param array $env
	 */
	protected function pushToStack($layout, $env) {
		$this->renderingStack[] = array($layout, $env);
	}
	
	/**
	 * Get current rendering
	 *
	 * @return array array($layout, $env);
	 */
	protected function getCurrentRendering() {
		return array_last($this->renderingStack);
	}
	
	/**
	 * Remove current rendering and get to previous one
	 */
	protected function pullFromStack() {
		array_pop($this->renderingStack);
	}
	
	/**
	 * Render the model
	 *
	 * @param string $layout The layout to use, default use is defined by child
	 * @param array $env An environment variable, commonly an array but depends on the rendering class used
	 * @return string The generated rendering.
	 *
	 * Render the model using $env.
	 * This function does not display the result, see display().
	 */
	public abstract function render($layout = null, $env = array());
	
	/**
	 * Display rendering
	 *
	 * @param string $layout The layout to use
	 * @param array $env An environment variable
	 *
	 * Display the model rendering using $env.
	 */
	public function display($layout = null, $env = array()) {
		echo $this->render($layout, $env);
	}
	
	/**
	 * Get menu items
	 *
	 * @param string $menu The menu to get items
	 * @return string[] The menu items
	 */
	public function getMenuItems($menu) {
		if( !isset(self::$menusConf) ) {
			self::$menusConf = IniConfig::build('menus', true);
		}
		if( empty(self::$menusConf) || empty(self::$menusConf->$menu) ) {
			return array();
		}
		return self::$menusConf->$menu;
	}
	
	/**
	 * Show the $menu
	 *
	 * @param string $menu The menu name
	 * @param string $layout the layout to use
	 * @param string $activeLink Active item link
	 */
	public function showMenu($menu, $layout = null, $activeLink= null) {
		
		if( $layout === null ) {
			$layout = defined('LAYOUT_MENU') ? LAYOUT_MENU : 'menu-default';
		}
		if( !$activeLink === null) {
			$activeLink = get_current_link();
		}
		
		$env = array('menu' => $menu, 'items' => array());
		$items = $this->getMenuItems($menu);
		if( empty($items) ) {
			return false;
		}
		foreach( $items as $itemConf ) {
			if( empty($itemConf) ) {
				continue;
			}
			if( $itemConf[0] === '\\' ) {
				// Callback
				$env['items'] = array_merge($env['items'], call_user_func($itemConf));
				
			} else {
				if( $itemConf[0] === '#' ) {
					// Static link with link & label hardcoded
					// Can not be active
					$itemParts = explodeList('|', substr($itemConf, 1), 2);
					$item = new MenuItem($itemParts[0], t($itemParts[1]));
					
				} else {
					// TODO: Allow {var:value} for values, or use a YAML config ?
					$routeName = $itemConf;
					
					/* @var $route HTTPRoute */
					$route = Route::getRoute($routeName);
					
					// Does not exist or is not acessible
					if( !$route || !$route->isAccessible()) {
						continue;
					}
					
					$item = new MenuItem(u($routeName), t($routeName));
					$item->setRoute($routeName);
				}
				$env['items'][] = $item;
			}
		}
		foreach( $env['items'] as $item) {
			if( $activeLink === $item->getLink() ) {
				$item->setActive();
			}
		}
		$this->display($layout, $env);
	}
	
	/**
	 * Show the rendering using a child rendering class
	 *
	 * @param array $env An environment variable
	 * @attention Require the use of a child class, you can not instantiate this one
	 *
	 * Show the $SHOWMODEL rendering using the child class.
	 * A call to this function terminate the running script.
	 * Default is the global environment.
	 */
	private static function show($env = null) {
		if( !isset($env) ) {
			$env = $GLOBALS;
		}
		
		static::$current->display(static::$SHOWMODEL, $env);
		
		exit();
	}
	
	/**
	 * Call the show function
	 *
	 * @see show()
	 *
	 * Calls the show function using the 'default_rendering' configuration.
	 * We should not use it anymore
	 */
	final public static function doShow() {
		static::$current->show();
	}
	
	/**
	 * Call the render function
	 *
	 * @param string $layout The model to use
	 * @param array $env An environment variable
	 * @return string The generated rendering
	 * @see render()
	 *
	 * Call the render function using the 'default_rendering' configuration.
	 * We should not use it anymore
	 */
	final public static function doRender($layout = null, $env = array()) {
		return static::$current->render($layout, $env);
	}
	
	/**
	 * Call the display function
	 *
	 * @param string $layout The model to use. Default value is null (behavior depending on renderer)
	 * @param array $env An array containing environment variables. Default value is null ($GLOBALS)
	 * @return boolean
	 * @see display()
	 *
	 * Calls the display function using the 'default_rendering' configuration.
	 * We should not use it anymore
	 */
	final public static function doDisplay($layout = null, $env = null) {
		if( $env === null ) {
			$env = $GLOBALS;
		}
		static::$current->display($layout, $env);
		return true;
	}
	
	/**
	 * The rendering layout stack
	 *
	 * @var array
	 */
	protected static $layoutStack = array();
	
	/**
	 * Use layout until the next endCurrentLayout()
	 *
	 * @param string $layout The layout to use.
	 * @see endCurrentLayout()
	 *
	 * Use layout until the next endCurrentLayout() is encountered.
	 *
	 * Warning: According to the ob_start() documentation, you can't call functions using output buffering in your layout.
	 * http://www.php.net/manual/en/function.ob-start.php#refsect1-function.ob-start-parameters
	 */
	public static function useLayout($layout) {
		static::$layoutStack[] = $layout;
		ob_start();
	}
	
	/**
	 * End the current layout
	 *
	 * @param array $env The environement to render the layout
	 * @return boolean False if there is no current layout
	 */
	public static function endCurrentLayout($env = array()) {
		if( !ob_get_level() || empty(static::$layoutStack) ) {
			return false;
		}
		$env['Content'] = ob_get_clean();// Ends and returns
		static::$current->display(array_pop(static::$layoutStack), $env);
		return true;
	}
	
	/**
	 * @return Rendering
	 */
	public static function getCurrent() {
		return self::$current;
	}
}
