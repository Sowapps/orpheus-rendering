<?php
/**
 * Rendering
 */

namespace Orpheus\Rendering;

use Exception;
use Orpheus\Config\IniConfig;
use Orpheus\Core\Route;
use Orpheus\InputController\HTTPController\HTTPRequest;
use Orpheus\InputController\HTTPController\HTTPRoute;
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
	 * The current global rendering
	 *
	 * @var Rendering
	 */
	protected static $current;
	/**
	 * The rendering layout stack
	 *
	 * @var array
	 */
	protected static $layoutStack = [];
	
	/**
	 * The current rendering stack
	 *
	 * @var array
	 */
	protected $renderingStack = [];
	
	public function __construct() {
	}
	
	/**
	 * Show the $menu
	 *
	 * @param string $menu The menu name
	 * @param string $layout the layout to use
	 * @param string $activeLink Active item link
	 * @throws Exception
	 */
	public function showMenu($menu, $layout = null, $activeLink = null) {
		
		if( $layout === null ) {
			$layout = defined('LAYOUT_MENU') ? LAYOUT_MENU : 'menu-default';
		}
		if( !$activeLink === null ) {
			$activeLink = get_current_link();
		}
		
		$controller = HTTPRequest::getMainHTTPRequest()->getController();
		$controllerValues = $controller->getValues();
		$env = ['menu' => $menu, 'items' => []];
		$items = $this->getMenuItems($menu);
		if( !$items ) {
			return;
		}
		foreach( $items as $itemConf ) {
			if( empty($itemConf) ) {
				continue;
			}
			if( $itemConf[0] === '\\' ) {
				// Callback
				if( is_callable($itemConf) ) {
					$env['items'] = array_merge($env['items'], call_user_func($itemConf));
					continue;
				} elseif( class_exists($itemConf) ) {
					$env['items'] = array_merge($env['items'], call_user_func([$itemConf, 'getItemList']));
					continue;
				} else {
					throw new Exception(sprintf('Special menu item but not a known function or class "%s"', $itemConf));
				}
				
			} elseif( $itemConf[0] === '#' ) {
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
				if( !$route || !$route->isAccessible() ) {
					continue;
				}
				$item = new MenuItem(u($routeName, $controllerValues), t($routeName));
				$item->setRoute($routeName);
			}
			$env['items'][] = $item;
		}
		foreach( $env['items'] as $item ) {
			if( $activeLink === $item->getLink() ) {
				$item->setActive();
			}
		}
		$this->display($layout, $env);
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
			return [];
		}
		return self::$menusConf->$menu;
	}
	
	/**
	 * Display rendering
	 *
	 * @param string $layout The layout to use
	 * @param array $env An environment variable
	 *
	 * Display the model rendering using $env.
	 */
	public function display($layout = null, $env = []) {
		echo $this->render($layout, $env);
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
	public abstract function render($layout = null, $env = []);
	
	/**
	 * Push rendering to stack
	 *
	 * @param string $layout
	 * @param array $env
	 */
	protected function pushToStack($layout, $env) {
		$this->renderingStack[] = [$layout, $env];
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
	final public static function doRender($layout = null, $env = []) {
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
	public function useLayout($layout, $block = 'Content') {
		static::$layoutStack[] = (object) ['layout' => $layout, 'block' => $block, 'caughtBlocks' => []];
		static::captureOutput();
	}
	
	/**
	 * Start capture of buffer
	 */
	public static function captureOutput() {
		ob_start();
	}
	
	/**
	 * Start new block capture
	 *
	 * @param $name
	 * @throws Exception
	 */
	public static function startNewBlock($name) {
		//End current block
		$result = static::endCapture();// Ends and returns
		$capture = array_last(static::$layoutStack);
		if( isset($capture->caughtBlocks[$capture->block]) ) {
			throw new Exception(sprintf('Block %s already rendered', $capture->block));
		};
		$capture->caughtBlocks[$capture->block] = $result;
		//Start new block
		$capture->block = $name;
		static::captureOutput();
	}
	
	/**
	 * End capture of buffer
	 *
	 * @return bool|false|string
	 */
	public static function endCapture() {
		if( ob_get_level() < 1 ) {
			return false;
		}
		return ob_get_clean();
	}
	
	/**
	 * End the current layout
	 *
	 * @param array $env The environment to render the layout
	 * @return boolean False if there is no current layout
	 */
	public function endCurrentLayout($env = []) {
		if( !ob_get_level() || empty(static::$layoutStack) ) {
			return false;
		}
		$result = static::endCapture();// Ends and returns
		$capture = array_pop(static::$layoutStack);
		$capture->caughtBlocks[$capture->block] = $result;
		$this->display($capture->layout, $capture->caughtBlocks + $env);
		return true;
	}
	
	/**
	 * @return static
	 */
	public static function getCurrent() {
		if( !static::$current ) {
			static::$current = new static();
		}
		return static::$current;
	}
	
}
