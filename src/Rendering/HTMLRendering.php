<?php
/**
 * HTMLRendering
 */

namespace Orpheus\Rendering;

/**
 * The HTML rendering class
 * 
 * A basic class to render HTML using PHP scripts.
*/
class HTMLRendering extends Rendering {

	/**
	 * The default model to show
	 *
	 * @var string
	 */
	protected static $SHOWMODEL		= 'page_skeleton';
	
	/**
	 * The theme to use to render HTML layouts
	 * 
	 * @var string
	 */
	public static $theme			= 'default';
	
	/**
	 * Path to css folder
	 * 
	 * @var string
	 */
	public static $cssPath			= 'css/';
	
	/**
	 * Path to js folder
	 * 
	 * @var string
	 */
	public static $jsPath			= 'js/';
	
	/**
	 * Path to models folder
	 * 
	 * @var string
	 */
	public static $modelsPath		= 'layouts/';
	
	/**
	 * List of CSS Urls to load
	 * 
	 * @var array
	 */
	public static $cssURLs			= array();
	
	/**
	 * List of JS Urls to load
	 * 
	 * @var array
	 */
	public static $jsURLs			= array();
	
	/**
	 * List of meta-properties to send
	 * 
	 * @var array
	 */
	public static $metaprop			= array();
	
	/**
	 * LINK_TYPE_PLUGIN
	 * 
	 * @var integer
	 */
	const LINK_TYPE_PLUGIN	= 1;
	
	/**
	 * LINK_TYPE_CUSTOM
	 * 
	 * @var integer
	 */
	const LINK_TYPE_CUSTOM	= 2;
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Orpheus\Rendering\Rendering::render()
	 * @param string $model The model to use, default use is defined by child
	 * @param array $env An environment variable, commonly an array but depends on the rendering class used
	 */
	public function render($model=null, $env=array()) {
		ob_start();
		$this->display($model, $env);
		return ob_get_clean();
	}
	
	/**
	 * Display the model, allow an absolute path to the template file.
	 * 
	 * {@inheritDoc}
	 * @see \Orpheus\Rendering\Rendering::display()
	 * @param string $model The model to use
	 * @param array $env An environment variable
	 */
	public function display($model=null, $env=array()) {
		if( $model === NULL ) {
			throw new \Exception("Invalid Rendering Model");
		}
		$rendering = $this->getCurrentRendering();
		if( $rendering ) {
// 			$env = array_merge($env, $rendering[1]);
			$env += $rendering[1];
		}
		
		// TODO Merge layoutStack and rendering stack
		$prevLayouts = count(static::$layoutStack);
		$this->pushToStack($model, $env);
		
		extract($env, EXTR_SKIP);
		// Store this to end layouts
		static::$current = $this;
		include static::getModelPath($model);
		
		$this->pullFromStack();
		$currentLayouts = count(static::$layoutStack);
		while( $currentLayouts > $prevLayouts && static::endCurrentLayout($env) ) {
			$currentLayouts--;
		}
	}
	
	/**
	 * Set the default theme used to render layouts
	 * 
	 * @param string $theme
	 */
	public static function setDefaultTheme($theme) {
		static::$theme = $theme;
	}
	
	/**
	 * Get the path to the $model
	 * 
	 * @param string $model
	 * @return string
	 */
	public static function getModelPath($model) {
		return is_readable($model) ? $model : static::getModelsPath().$model.'.php';
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
		if( file_exists(static::getModelPath('report-'.$type)) ) {
			return static::doRender('report-'.$type, array('Report'=>$report, 'Domain'=>$domain, 'Type'=>$type, 'Stream'=>$stream));
		}
		if( file_exists(static::getModelPath('report')) ) {
			return static::doRender('report', array('Report'=>$report, 'Domain'=>$domain, 'Type'=>$type, 'Stream'=>$stream));
		}
		return '
		<div class="report report_'.$stream.' '.$type.' '.$domain.'">'.nl2br($report).'</div>';
	}
	
	/**
	 * Add a theme css file to the list
	 * 
	 * @param string $filename
	 * @param string $type
	 */
	public static function addThemeCSSFile($filename, $type=null) {
		static::addCSSURL(static::getCSSURL().$filename, $type);
	}
	
	/**
	 * Add a global css file to the list
	 * 
	 * @param string $filename
	 * @param string $type
	 */
	public static function addCSSFile($filename, $type=null) {
		static::addThemeCSSFile($filename, $type);
	}
	
	/**
	 * Add a css url to the list
	 * 
	 * @param string $url
	 * @param string $type
	 */
	public static function addCSSURL($url, $type=null) {
		static::addTypedURL(static::$cssURLs, $url, $type);
	}

	/**
	 * Add a theme js file to the list
	 *
	 * @param string $filename
	 * @param string $type
	 */
	public static function addThemeJSFile($filename, $type=null) {
		static::addJSURL(static::getJSURL().$filename, $type);
	}

	/**
	 * Add a global js file to the list
	 *
	 * @param string $filename
	 * @param string $type
	 */
	public static function addJSFile($filename, $type=null) {
		static::addJSURL(JSURL.$filename, $type);
	}

	/**
	 * Add a js url to the list
	 *
	 * @param string $url
	 * @param string $type
	 */
	public static function addJSURL($url, $type=null) {
		static::addTypedURL(static::$jsURLs, $url, $type);
	}


	/**
	 * Add a meta-propertie to the list
	 *
	 * @param string $property
	 * @param string $content
	 */
	public static function addMetaProperty($property, $content) {
		static::$metaprop[$property] = $content;
	}
	
	/**
	 * List all registered css URLs
	 * 
	 * @param string $type
	 * @return array
	 */
	public static function listCSSURLs($type=null) {
		return static::listTypedURL(static::$cssURLs, $type);
	}
	
	/**
	 * List all registered js URLs
	 * 
	 * @param string $type
	 * @return array
	 */
	public static function listJSURLs($type=null) {
		return static::listTypedURL(static::$jsURLs, $type);
	}
	
	/**
	 * Add an $url by $type in $array
	 * 
	 * @param array $array
	 * @param string $url
	 * @param string $type
	 */
	protected static function addTypedURL(&$array, $url, $type=null) {
		if( !$type ) {
			$type = self::LINK_TYPE_CUSTOM;
		}
		if( !isset($array[$type]) ) {
			$array[$type] = array();
		}
		$array[$type][] = $url;
	}
	
	/**
	 * List urls by $type in $array
	 * 
	 * @param array $array
	 * @param string $type
	 * @return array
	 */
	protected static function listTypedURL(&$array, $type=null) {
		if( $type ) {
			if( !isset($array[$type]) ) {
				return array();
			}
			$r = $array[$type];
			unset($array[$type]);
			return $r;
		}
		$r = array();
		foreach( $array as $type => $typeURLs ) {
// 			if( !is_array($typeURLs) ) {
// 				debug('$array', $array);
// 				die();
// 			}
			$r = array_merge($r, $typeURLs);
		}
		$array = array();
		return $r;
	}
	
	/**
	 * Get the theme path.
	 * 
	 * @return The theme path
	 * 
	 * Get the path to the current theme.
	 */
	public static function getThemePath() {
		return ACCESSPATH.THEMESDIR.static::$theme.'/';
	}

	/** 
	 * Get the absolute theme path.
	 *
	 * @return The theme path.
	 *
	 * Get the absolute path to the current theme.
	 */
	public static function getAbsThemePath() {
		return pathOf(static::getThemePath());
	}
	
	/**
	 * Get the models theme path
	 * 
	 * @return string The models theme path
	 */
	public static function getModelsPath() {
		return static::getThemePath().static::$modelsPath;
	}
	
	/**
	 * Get the css theme path
	 * 
	 * @return string The css theme path
	 */
	public static function getCSSPath() {
		return static::getThemePath().static::$cssPath;
	}


	/**
	 * Get the theme url
	 *
	 * @return string
	 */
	public static function getThemeURL() {
		return THEMESURL.static::$theme.'/';
	}

	/** 
	 * Get the CSS files url
	 * 
	 * @return string The CSS url
	*/
	public static function getCSSURL() {
		return static::getThemeURL().static::$cssPath;
	}

	/** 
	 * Get the JS files url
	 * 
	 * @return string The JS url
	*/
	public static function getJSURL() {
		return static::getThemeURL().static::$jsPath;
	}
}
