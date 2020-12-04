<?php
/**
 * HTMLRendering
 */

namespace Orpheus\Rendering;

use Exception;
use Orpheus\Config\IniConfig;

/**
 * The HTML rendering class
 *
 * A basic class to render HTML using PHP scripts.
 */
class HTMLRendering extends Rendering {
	
	/**
	 * LINK_TYPE_PLUGIN
	 *
	 * @var integer
	 */
	const LINK_TYPE_PLUGIN = 1;
	
	/**
	 * LINK_TYPE_CUSTOM
	 *
	 * @var integer
	 */
	const LINK_TYPE_CUSTOM = 2;
	
	/**
	 * The default theme to use for $theme
	 *
	 * @var string
	 */
	public static $defaultTheme;
	
	/**
	 * The default model to show
	 *
	 * @var string
	 */
	protected static $SHOWMODEL = 'page_skeleton';
	
	/**
	 * The current global rendering
	 *
	 * @var Rendering
	 */
	protected static $current;
	
	/**
	 * List of CSS Urls to load
	 *
	 * @var array
	 */
	public $cssURLs = [];
	
	/**
	 * List of JS Urls to load
	 *
	 * @var array
	 */
	public $jsURLs = [];
	
	/**
	 * List of meta-properties to send
	 *
	 * @var array
	 */
	public $metaprop = [];
	
	/**
	 * Path to css folder
	 *
	 * @var string
	 */
	public $cssPath = 'css/';
	
	/**
	 * Path to js folder
	 *
	 * @var string
	 */
	public $jsPath = 'js/';
	
	/**
	 * Path to layouts folder
	 *
	 * @var string
	 */
	public $layoutsPath = 'layouts/';
	
	/** @var int */
	public $renderingId = 0;
	
	/** @var string */
	public $resourcePath = ACCESSPATH;
	
	/** @var string */
	public $themeFolderUri;
	
	/** @var bool */
	public $remote = true;
	
	/**
	 * The theme to use to render HTML layouts
	 *
	 * @var string
	 */
	protected $theme;
	
	public function __construct() {
		parent::__construct();
		$this->theme = static::getDefaultTheme();
	}
	
	/**
	 * Get the default theme used to render layouts
	 *
	 * @return string $theme
	 */
	public static function getDefaultTheme() {
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
	public static function setDefaultTheme($theme) {
		static::$defaultTheme = $theme;
	}
	
	/**
	 * Add a global css file to the list
	 *
	 * @param string $filename
	 * @param string $type
	 */
	public function addCssFile($filename, $type = null) {
		$this->addThemeCssFile($filename, $type);
	}
	
	/**
	 * Add a theme css file to the list
	 *
	 * @param string $filename
	 * @param string $type
	 */
	public function addThemeCssFile($filename, $type = null) {
		$this->addCssUrl(($this->isRemote() ? $this->getCssUrl() : $this->getCssPath()) . $filename, $type);
	}
	
	/**
	 * Add a css url to the list
	 *
	 * @param string $url
	 * @param string $type
	 */
	public function addCssUrl($url, $type = null) {
		static::addTypedUrl($this->cssURLs, $url, $type);
	}
	
	/**
	 * Add an $url by $type in $array
	 *
	 * @param array $array
	 * @param string $url
	 * @param string $type
	 */
	protected static function addTypedUrl(&$array, $url, $type = null) {
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
	public function isRemote() {
		return $this->remote;
	}
	
	/**
	 * @param bool $remote
	 */
	public function setRemote($remote) {
		$this->remote = $remote;
	}
	
	/**
	 * Get the CSS files url
	 *
	 * @return string The CSS url
	 */
	public function getCssUrl() {
		return $this->getThemeUrl() . $this->cssPath;
	}
	
	/**
	 * Get the theme url
	 *
	 * @return string
	 */
	public function getThemeUrl() {
		return $this->getThemeFolderUri() . '/' . $this->theme . '/';
	}
	
	/**
	 * Get Theme folder Url. Mainly, a http URL for a website.
	 *
	 * @return string
	 * @throws Exception
	 */
	public function getThemeFolderUri() {
		if( !$this->themeFolderUri ) {
			if( defined('THEMES_URL') ) {
				$this->themeFolderUri = THEMES_URL;
				
			} elseif( defined('THEMESURL') ) {
				$this->themeFolderUri = THEMESURL;
				
			} else {
				throw new Exception('No theme folder URI provided, please use setThemeFolderUri or define THEMES_URL');
			}
		}
		return $this->themeFolderUri;
	}
	
	/**
	 * @param string $themeFolderUri
	 */
	public function setThemeFolderUri($themeFolderUri) {
		$this->themeFolderUri = $themeFolderUri;
	}
	
	/**
	 * Get the css theme path
	 *
	 * @return string The css theme path
	 */
	public function getCssPath() {
		return $this->getThemePath() . $this->cssPath;
	}
	
	/**
	 * Get the theme path.
	 *
	 * @return string The theme path
	 *
	 * Get the path to the current theme.
	 */
	public function getThemePath() {
		return $this->getResourcePath() . THEMES_FOLDER . '/' . $this->theme . '/';
	}
	
	/**
	 * @return string
	 */
	public function getResourcePath() {
		return $this->resourcePath;
	}
	
	/**
	 * @param string $resourcePath
	 */
	public function setResourcePath(string $resourcePath) {
		$this->resourcePath = $resourcePath;
		return $this;
	}
	
	/**
	 * Add a theme js file to the list
	 *
	 * @param string $filename
	 * @param string $type
	 */
	public function addThemeJsFile($filename, $type = null) {
		$this->addJsUrl(($this->isRemote() ? $this->getJsUrl() : $this->getJsPath()) . $filename, $type);
	}
	
	/**
	 * Add a js url to the list
	 *
	 * @param string $url
	 * @param string $type
	 */
	public function addJsUrl($url, $type = null) {
		static::addTypedUrl($this->jsURLs, $url, $type);
	}
	
	/**
	 * Get the JS files url
	 *
	 * @return string The JS url
	 */
	public function getJsUrl() {
		return $this->getThemeUrl() . $this->jsPath;
	}
	
	/**
	 * Get the js theme path
	 *
	 * @return string The JS theme path
	 */
	public function getJsPath() {
		return $this->getThemePath() . $this->jsPath;
	}
	
	/**
	 * Add a global js file to the list
	 *
	 * @param string $filename
	 * @param string $type
	 */
	public function addJsFile($filename, $type = null) {
		$this->addJsUrl(JS_URL . '/' . $filename, $type);
	}
	
	/**
	 * Add a html meta property to the list
	 *
	 * @param string $property
	 * @param string $content
	 */
	public function addMetaProperty($property, $content) {
		$this->metaprop[$property] = $content;
	}
	
	/**
	 * List all registered css URLs
	 *
	 * @param string $type
	 * @return array
	 */
	public function listCssUrls($type = null) {
		return $this->listTypedUrl($this->cssURLs, $type);
	}
	
	/**
	 * List urls by $type in $array
	 *
	 * @param array $array
	 * @param string $type
	 * @return array
	 */
	protected static function listTypedUrl(&$array, $type = null) {
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
	 * List all registered js URLs
	 *
	 * @param string $type
	 * @return array
	 */
	public function listJsUrls($type = null) {
		return static::listTypedUrl($this->jsURLs, $type);
	}
	
	/**
	 * List all registered html meta properties
	 *
	 * @return array
	 */
	public function listMetaProperties() {
		return $this->metaprop;
	}
	
	/**
	 * Set the theme used to render layouts
	 *
	 * @param string $theme
	 */
	public function setTheme($theme) {
		$this->theme = $theme;
	}
	
	/**
	 * Get the absolute theme path.
	 *
	 * @return string The theme path.
	 *
	 * Get the absolute path to the current theme.
	 */
	public function getAbsThemePath() {
		return pathOf($this->getThemePath());
	}
	
	/**
	 * Get the models theme path
	 *
	 * @return string The models theme path
	 * @deprecated Use getLayoutsPath()
	 */
	public function getModelsPath() {
		return $this->getLayoutsPath();
	}
	
	/**
	 * Get the layout theme path
	 *
	 * @return string The models theme path
	 */
	public function getLayoutsPath() {
		return $this->getThemePath() . $this->layoutsPath;
	}
	
	/**
	 * @return int
	 */
	public function getRenderingId() {
		return $this->renderingId;
	}
	
	/**
	 * Render the given report as HTML
	 *
	 * @param string $report
	 * @param string $domain
	 * @param string $type
	 * @param string $stream
	 * @return string
	 */
	public static function renderReport($report, $domain, $type, $stream) {
		$report = nl2br($report);
		$rendering = new static();
		if( $rendering->existsLayoutPath('report-' . $type) ) {
			return $rendering->render('report-' . $type, ['Report' => $report, 'Domain' => $domain, 'Type' => $type, 'Stream' => $stream]);
		}
		if( $rendering->existsLayoutPath('report') ) {
			return $rendering->render('report', ['Report' => $report, 'Domain' => $domain, 'Type' => $type, 'Stream' => $stream]);
		}
		return '
		<div class="report report_' . $stream . ' ' . $type . ' ' . $domain . '">' . nl2br($report) . '</div>';
	}
	
	/**
	 * Test if the layout exists
	 *
	 * @param string $layout
	 * @return string
	 */
	public function existsLayoutPath($layout) {
		return is_readable($this->getLayoutPath($layout));
	}
	
	/**
	 * Get the path to the $layout
	 *
	 * @param string $layout
	 * @return string
	 */
	public function getLayoutPath($layout) {
		return is_readable($layout) ? $layout : $this->getLayoutsPath() . $layout . '.php';
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @param string $layout The model to use, default use is defined by child
	 * @param array $env An environment variable, commonly an array but depends on the rendering class used
	 */
	public function render($layout = null, $env = []) {
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
	 * {@inheritDoc}
	 * @param string $layout The model to use
	 * @param array $env An environment variable
	 */
	public function display($layout = null, $env = []) {
		if( $layout === null ) {
			throw new Exception('Invalid Rendering Model');
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
		
		// Store this to end layouts, static because ob_* functions are globals
		include $this->getLayoutPath($layout);
		
		$this->pullFromStack();
		$currentLayouts = count(static::$layoutStack);
		while( $currentLayouts > $prevLayouts && static::endCurrentLayout($env) ) {
			$currentLayouts--;
		}
	}
	
}
