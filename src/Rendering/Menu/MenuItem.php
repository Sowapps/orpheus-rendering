<?php

namespace Orpheus\Rendering\Menu;

/**
 * Class MenuItem
 *
 * @package Orpheus\Rendering\Menu
 * @property bool $current
 */
class MenuItem {
	
	/**
	 * @var string
	 */
	protected $link;
	
	/**
	 * @var string
	 */
	protected $label;
	
	/**
	 * @var string
	 */
	protected $route;
	
	/**
	 * @var boolean
	 */
	protected $isActive = false;
	
	/**
	 * @var boolean
	 */
	protected $isGroup = false;
	
	public function __construct($link, $label) {
		$this->setLink($link);
		$this->setLabel($label);
	}
	
	public function __get($key) {
		if( $key === 'current' ) {
			// Backward compatibility
			$key = 'isActive';
		}
		return isset($this->{$key}) ? $this->{$key} : null;
	}
	
	public function getLink() {
		return $this->link;
	}
	
	public function setLink($link) {
		$this->link = $link;
		return $this;
	}
	
	public function getLabel() {
		return $this->label;
	}
	
	public function setLabel($label) {
		$this->label = $label;
		return $this;
	}
	
	public function getRoute() {
		return $this->route;
	}
	
	public function setRoute($route) {
		$this->route = $route;
		return $this;
	}
	
	public function isActive() {
		return $this->isActive;
	}
	
	public function setIsActive($isActive) {
		$this->isActive = $isActive;
		return $this;
	}
	
	public function setActive() {
		return $this->setIsActive(true);
	}
	
}
