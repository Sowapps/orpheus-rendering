<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Orpheus\Rendering;

/**
 * The raw rendering class
 *
 * A class to render module display without any treatment.
 */
class RawRendering extends Rendering {
	
	/**
	 * @param $model
	 * @param $env
	 * @return mixed|string|null
	 */
	public function render($model = null, $env = []): ?string {
		return $env['CONTROLLER_OUTPUT'] ?? '';
	}
	
}
