<?php
/**
 * RawRendering
 */

namespace Orpheus\Rendering;

/**
 * The raw rendering class
 * 
 * A class to render module display without any treatment.
 */
class RawRendering extends Rendering {
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Orpheus\Rendering\Rendering::render()
	 */
	public function render($model=null, $env=array()) {
		return isset($env['CONTROLLER_OUTPUT']) ? $env['CONTROLLER_OUTPUT'] : '';
	}
}
