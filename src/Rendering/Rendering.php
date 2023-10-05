<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Orpheus\Rendering;

use Exception;
use Orpheus\Config\Config;
use Orpheus\Config\IniConfig;
use Orpheus\InputController\HttpController\HttpRequest;
use Orpheus\InputController\HttpController\HttpRoute;
use Orpheus\Rendering\Menu\MenuItem;
use RuntimeException;

/**
 * The rendering class
 *
 * This class is the core for custom rendering use.
 */
abstract class Rendering {
	
	/**
	 * The current global rendering
	 *
	 * @var Rendering
	 */
	protected static Rendering $current;
	
	/**
	 * The configuration of the menu
	 *
	 * @var Config
	 */
	protected static Config $menusConfig;
	
	/**
	 * The rendering layout stack
	 *
	 * @var array
	 */
	protected static array $layoutStack = [];
	
	/**
	 * The current rendering stack
	 *
	 * @var array
	 */
	protected array $renderingStack = [];
	
	/**
	 * Show the $menu
	 *
	 * @param string $menu The menu name
	 * @param string|null $layout the layout to use
	 * @param string|null $activeLink Active item link
	 * @throws Exception
	 */
	public function showMenu(string $menu, ?string $layout = null, ?string $activeLink = null): void {
		if( $layout === null ) {
			$layout = 'menu.default';
		}
		if( $activeLink === null ) {
			$activeLink = get_current_link();
		}
		
		$controller = HttpRequest::getMainHttpRequest()->getController();
		$controllerValues = $controller->getValues();
		$env = ['menu' => $menu, 'items' => []];
		$items = $this->getMenuItems($menu);
		if( !$items ) {
			return;
		}
		/** @var MenuItem $item */
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
					throw new RuntimeException(sprintf('Special menu item but not a known function or class "%s"', $itemConf));
				}
				
			} elseif( $itemConf[0] === '#' ) {
				// Static link with link & label hardcoded
				// Can not be active
				$itemParts = explodeList('|', substr($itemConf, 1), 2);
				$item = new MenuItem($itemParts[0], t($itemParts[1]));
				
			} else {
				// TODO: Allow {var:value} for values, or use a Yaml config ?
				$routeName = $itemConf;
				
				/* @var $route HttpRoute */
				$route = HttpRoute::getRoute($routeName);
				
				// Does not exist or is not accessible
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
				$item->setActive(true);
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
	public function getMenuItems(string $menu): array {
		if( !isset(self::$menusConfig) ) {
			self::$menusConfig = IniConfig::build('menus', true);
		}
		if( empty(self::$menusConfig) || empty(self::$menusConfig->$menu) ) {
			return [];
		}
		
		return self::$menusConfig->$menu;
	}
	
	/**
	 * Display rendering
	 *
	 * @param string|null $layout The layout to use
	 * @param array $env An environment variable
	 */
	public function display(?string $layout = null, array $env = []): void {
		echo $this->render($layout, $env);
	}
	
	/**
	 * Render the model
	 * This function does not display the result, see display().
	 *
	 * @param string|null $layout The layout to use, default use is defined by child
	 * @param array $env An environment variable, commonly an array but depends on the rendering class used
	 * @return string|null The generated rendering
	 */
	public abstract function render(?string $layout = null, array $env = []): ?string;
	
	/**
	 * Push rendering to stack
	 */
	protected function pushToStack(string $layout, array $env): void {
		$this->renderingStack[] = [$layout, $env];
	}
	
	/**
	 * Get current rendering
	 *
	 * @return array|null array($layout, $env);
	 */
	protected function getCurrentRendering(): ?array {
		return array_last($this->renderingStack) ?: null;
	}
	
	/**
	 * Remove current rendering and get to previous one
	 */
	protected function pullFromStack(): void {
		array_pop($this->renderingStack);
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
	public function useLayout(string $layout, string $block = 'content'): void {
		static::$layoutStack[] = (object) ['layout' => $layout, 'block' => $block, 'caughtBlocks' => []];
		static::captureOutput();
	}
	
	/**
	 * Start capture of buffer
	 */
	public static function captureOutput(): void {
		ob_start();
	}
	
	/**
	 * Start new block capture
	 */
	public function startNewBlock(string $name): void {
		//End current block
		$result = static::endCapture();// Ends and returns
		$capture = array_last(static::$layoutStack);
		if( isset($capture->caughtBlocks[$capture->block]) ) {
			throw new RuntimeException(sprintf('Block %s already rendered', $capture->block));
		}
		$capture->caughtBlocks[$capture->block] = $result;
		//Start new block
		$capture->block = $name;
		static::captureOutput();
	}
	
	/**
	 * End capture of buffer
	 */
	public static function endCapture(): bool|string {
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
	public function endCurrentLayout(array $env = []): bool {
		if( !ob_get_level() || empty(static::$layoutStack) ) {
			return false;
		}
		$result = static::endCapture();// Ends and returns
		$capture = array_pop(static::$layoutStack);
		$capture->caughtBlocks[$capture->block] = $result;
		$this->display($capture->layout, $capture->caughtBlocks + $env);
		
		return true;
	}
	
	public static function getCurrent(): static {
		return static::$current ??= new static();
	}
	
}
