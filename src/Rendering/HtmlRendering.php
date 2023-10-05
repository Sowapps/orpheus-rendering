<?php /** @noinspection ALL */
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Orpheus\Rendering;

use Exception;
use Orpheus\Config\IniConfig;
use RuntimeException;

/**
 * The HTML rendering class
 *
 * A basic class to render HTML using PHP scripts.
 */
class HtmlRendering extends Rendering {
	
	/**
	 * @var integer
	 */
	const LINK_TYPE_PLUGIN = 1;
	
	/**
	 * @var integer
	 */
	const LINK_TYPE_CUSTOM = 2;
	
	/**
	 * The default theme to use for $theme
	 *
	 * @var string|null
	 */
	public static ?string $defaultTheme = null;
	
	/**
	 * The current global rendering
	 *
	 * @var Rendering
	 */
	protected static Rendering $current;
	
	/**
	 * List of CSS Urls to load
	 *
	 * @var array
	 */
	public array $cssURLs = [];
	
	/**
	 * List of JS Urls to load
	 *
	 * @var array
	 */
	public array $jsURLs = [];
	
	/**
	 * List of meta-properties to send
	 *
	 * @var array
	 */
	public array $metaprop = [];
	
	/**
	 * Path to css folder
	 *
	 * @var string
	 */
	public string $cssPath = '/css';
	
	/**
	 * Path to js folder
	 *
	 * @var string
	 */
	public string $jsPath = '/js';
	
	/**
	 * Path to layouts folder
	 *
	 * @var string
	 */
	public string $layoutsPath = '/layouts';
	
	/** @var int */
	public int $renderingId = 0;
	
	/** @var string */
	public ?string $resourcePath = null;
	
	/** @var string */
	public ?string $themeFolderUri = null;
	
	/** @var bool */
	public bool $remote = true;
	
	/**
	 * The theme to use to render HTML layouts
	 *
	 * @var string
	 */
	protected $theme;
	
	public function __construct() {
		$this->resourcePath = ACCESS_PATH;
		$this->theme = static::getDefaultTheme();
	}
	
	/**
	 * Get the default theme used to render layouts
	 *
	 * @return string $theme
	 */
	public static function getDefaultTheme(): string {
		if( !static::$defaultTheme ) {
			static::$defaultTheme = IniConfig::get('default_html_theme', 'default');
		}
		
		return static::$defaultTheme;
	}
	
	/**
	 * Set the default theme used to render layouts
	 *
	 * @param string $theme
	 */
	public static function setDefaultTheme(string $theme) {
		static::$defaultTheme = $theme;
	}
	
	/**
	 * Add a global css file to the list
	 *
	 * @param string $filename
	 * @param string $type
	 */
	public function addCssFile(string $filename, ?string $type = null) {
		$this->addThemeCssFile($filename, $type);
	}
	
	/**
	 * Add a theme css file to the list
	 *
	 * @param string $filename
	 * @param string $type
	 */
	public function addThemeCssFile(string $filename, ?string $type = null) {
		if( $filename[0] !== '/' ) {
			$filename = '/' . $filename;
		}
		$this->addCssUrl(($this->isRemote() ? $this->getCssUrl() : $this->getCssPath()) . $filename, $type);
	}
	
	/**
	 * Add a css url to the list
	 *
	 * @param string $url
	 * @param string $type
	 */
	public function addCssUrl(string $url, ?string $type = null) {
		static::addTypedUrl($this->cssURLs, $url, $type);
	}
	
	/**
	 * Add an $url by $type in $array
	 *
	 * @param array $array
	 * @param string $url
	 * @param string|null $type
	 */
	protected static function addTypedUrl(array &$array, string $url, ?string $type = null) {
		if( !$type ) {
			$type = self::LINK_TYPE_CUSTOM;
		}
		if( !isset($array[$type]) ) {
			$array[$type] = [];
		}
		$array[$type][] = $url;
	}
	
	/**
	 * @return bool
	 */
	public function isRemote(): bool {
		return $this->remote;
	}
	
	/**
	 * @param bool $remote
	 */
	public function setRemote(bool $remote): self {
		$this->remote = $remote;
		
		return $this;
	}
	
	/**
	 * Get the theme url
	 *
	 * @return string
	 */
	public function getThemeUrl(): string {
		return $this->getThemeFolderUri() . '/' . $this->theme;
	}
	
	/**
	 * Get Theme folder Url. Mainly, a http URL for a website.
	 *
	 * @return string
	 */
	public function getThemeFolderUri(): string {
		if( !$this->themeFolderUri ) {
			if( defined('THEMES_URL') ) {
				$this->themeFolderUri = THEMES_URL;
				
			} elseif( defined('THEMESURL') ) {
				$this->themeFolderUri = THEMESURL;
				
			} else {
				throw new RuntimeException('No theme folder URI provided, please use setThemeFolderUri or define THEMES_URL');
			}
		}
		
		return $this->themeFolderUri;
	}
	
	/**
	 * @param string $themeFolderUri
	 */
	public function setThemeFolderUri(string $themeFolderUri): self {
		$this->themeFolderUri = $themeFolderUri;
		
		return $this;
	}
	
	/**
	 * Get the css theme path
	 *
	 * @return string The css theme path
	 */
	public function getCssPath(): string {
		return $this->getThemePath() . $this->cssPath;
	}
	
	/**
	 * Get the CSS files url
	 *
	 * @return string The CSS url
	 */
	public function getCssUrl(): string {
		return $this->getThemeUrl() . $this->cssPath;
	}
	
	/**
	 * Get the theme path.
	 *
	 * @return string The theme path
	 *
	 * Get the path to the current theme.
	 */
	public function getThemePath(): string {
		return $this->getResourcePath() . THEMES_FOLDER . '/' . $this->theme;
	}
	
	/**
	 * @return string
	 */
	public function getResourcePath(): string {
		return $this->resourcePath;
	}
	
	/**
	 * @param string $resourcePath
	 * @return HtmlRendering
	 */
	public function setResourcePath(string $resourcePath): HtmlRendering {
		$this->resourcePath = $resourcePath;
		
		return $this;
	}
	
	/**
	 * Add a theme js file to the list
	 *
	 * @param string $filename
	 * @param string|null $type
	 * @return HtmlRendering
	 */
	public function addThemeJsFile(string $filename, ?string $type = null): self {
		if( $filename[0] !== '/' ) {
			$filename = '/' . $filename;
		}
		$this->addJsUrl(($this->isRemote() ? $this->getJsUrl() : $this->getJsPath()) . $filename, $type);
		
		return $this;
	}
	
	/**
	 * Add a js url to the list
	 *
	 * @param string $url
	 * @param string $type
	 * @return HtmlRendering
	 */
	public function addJsUrl($url, $type = null): self {
		static::addTypedUrl($this->jsURLs, $url, $type);
		
		return $this;
	}
	
	/**
	 * Get the JS files url
	 *
	 * @return string The JS url
	 */
	public function getJsUrl(): string {
		return $this->getThemeUrl() . $this->jsPath;
	}
	
	/**
	 * Get the js theme path
	 *
	 * @return string The JS theme path
	 */
	public function getJsPath(): string {
		return $this->getThemePath() . $this->jsPath;
	}
	
	/**
	 * Add a global js file to the list
	 *
	 * @param string $filename
	 * @param string|null $type
	 * @return HtmlRendering
	 */
	public function addJsFile(string $filename, ?string $type = null): self {
		$this->addJsUrl(JS_URL . '/' . $filename, $type);
		
		return $this;
	}
	
	/**
	 * Add a html meta property to the list
	 *
	 * @param string $property
	 * @param string $content
	 */
	public function addMetaProperty(string $property, string $content): void {
		$this->metaprop[$property] = $content;
	}
	
	/**
	 * List urls by $type in $array
	 *
	 * @param array $array
	 * @param string|null $type
	 * @return array
	 */
	protected static function listTypedUrl(array &$array, ?string $type = null): array {
		if( $type ) {
			if( !isset($array[$type]) ) {
				return [];
			}
			$r = $array[$type];
			unset($array[$type]);
			
			return $r;
		}
		$r = [];
		foreach( $array as $type => $typeURLs ) {
			$r = array_merge($r, $typeURLs);
		}
		$array = [];
		
		return $r;
	}
	
	/**
	 * List all registered css URLs
	 *
	 * @param string $type
	 * @return array
	 */
	public function listCssUrls($type = null): array {
		return $this->listTypedUrl($this->cssURLs, $type);
	}
	
	/**
	 * List all registered js URLs
	 *
	 * @param string $type
	 * @return array
	 */
	public function listJsUrls($type = null): array {
		return static::listTypedUrl($this->jsURLs, $type);
	}
	
	/**
	 * List all registered html meta properties
	 *
	 * @return array
	 */
	public function listMetaProperties(): array {
		return $this->metaprop;
	}
	
	/**
	 * Set the theme used to render layouts
	 *
	 * @param string $theme
	 */
	public function setTheme(string $theme) {
		$this->theme = $theme;
	}
	
	/**
	 * Get the absolute theme path.
	 *
	 * @return string The theme path.
	 *
	 * Get the absolute path to the current theme.
	 * @throws Exception
	 */
	public function getAbsThemePath(): string {
		return pathOf($this->getThemePath());
	}
	
	/**
	 * Get the layout theme path
	 *
	 * @return string The models theme path
	 */
	public function getLayoutsPath(): string {
		return $this->getThemePath() . $this->layoutsPath;
	}
	
	/**
	 * @return int
	 */
	public function getRenderingId(): int {
		return $this->renderingId;
	}
	
	/**
	 * Test if the layout exists
	 *
	 * @param string $layout
	 * @return bool
	 */
	public function existsLayoutPath(string $layout): bool {
		return is_readable($this->getLayoutPath($layout));
	}
	
	/**
	 * Get the path to the $layout
	 *
	 * @param string $layout
	 * @return string
	 */
	public function getLayoutPath(string $layout): string {
		return is_readable($layout) ? $layout : $this->getLayoutsPath() . '/' . $layout . '.php';
	}
	
	/**
	 * @param null $layout The model to use, default use is defined by child
	 * @param array $env An environment variable, commonly an array but depends on the rendering class used
	 * @return string|null
	 * @throws Exception
	 */
	public function render(?string $layout = null, array $env = []): ?string {
		static::captureOutput();
		$success = false;
		try {
			$this->display($layout, $env);
			$success = true;
		} finally {
			$contents = static::endCapture();
		}
		
		return $success ? $contents : null;
	}
	
	/**
	 * Display the model, allow an absolute path to the template file.
	 *
	 * @param string|null $layout The model to use
	 * @param array $env An environment variable
	 */
	public function display(?string $layout = null, array $env = []): void {
		if( !$layout ) {
			throw new RuntimeException('Invalid Rendering Model');
		}
		$this->renderingId++;
		$rendering = $this->getCurrentRendering();
		if( $rendering ) {
			$env += $rendering[1];
		}
		
		// TODO Merge layoutStack and rendering stack
		$prevLayouts = count(static::$layoutStack);
		$this->pushToStack($layout, $env);
		
		extract($env, EXTR_SKIP);
		// Variable for included template
		$rendering = $this;
		
		$interrupted = true;
		try {
			// Store this to end layouts, static because ob_* functions are globals
			include $this->getLayoutPath($layout);
			$interrupted = false;
		} finally {
			$this->pullFromStack();
			$currentLayouts = count(static::$layoutStack);
			while( $currentLayouts > $prevLayouts ) {
				// If interrupted, we just end started layout's capture
				$result = $interrupted ? static::endCapture() : static::endCurrentLayout($env);
				if( $result ) {
					$currentLayouts--;
				} else {
					// In fact, there is no more capture in progress
					break;
				}
			}
		}
	}
	
}
