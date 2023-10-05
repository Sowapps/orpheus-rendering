<?php

namespace Orpheus\Rendering\Menu;

/**
 * Class MenuItem
 *
 * @property bool $current
 */
class MenuItem {
	
	protected string $link;
	
	protected string $label;
	
	protected ?string $route = null;
	
	protected bool $active = false;
	
	public function __construct(string $link, string $label) {
		$this->setLink($link);
		$this->setLabel($label);
	}
	
	public function __get(string $key) {
		if( $key === 'current' ) {
			// Backward compatibility
			$key = 'active';
		}
		/** @noinspection PhpExpressionAlwaysNullInspection */
		return $this->{$key} ?? null;
	}
	
	public function getLink(): string {
		return $this->link;
	}
	
	public function setLink(string $link): static {
		$this->link = $link;
		return $this;
	}
	
	public function getLabel(): string {
		return $this->label;
	}
	
	public function setLabel(string $label): static {
		$this->label = $label;
		return $this;
	}
	
	public function getRoute(): ?string {
		return $this->route;
	}
	
	public function setRoute(string $route): static {
		$this->route = $route;
		return $this;
	}
	
	public function isActive(): bool {
		return $this->active;
	}
	
	public function setActive(bool $active): static {
		$this->active = $active;
		return $this;
	}
	
}
