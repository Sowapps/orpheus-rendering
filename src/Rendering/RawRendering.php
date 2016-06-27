<?php
namespace Orpheus\Rendering;

/**
 * The raw rendering class
 * 
 * A class to render module display without any treatment.
 */
class RawRendering extends Rendering {
	
	/**
	 * Render the model.
	 * 
	 * @see Rendering::render()
	 */
	public function render($model=null, $env=array()) {
// 		extract($env);
// 		if( !isset($Page) ) {
// 			$Page = '';
// 		}
		return isset($env['CONTROLLER_OUTPUT']) ? $env['CONTROLLER_OUTPUT'] : '';
	}
}
