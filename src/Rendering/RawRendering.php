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
	
	public function render(?string $layout = null, array $env = []): ?string {
		return $env['CONTROLLER_OUTPUT'] ?? '';
	}
	
}
