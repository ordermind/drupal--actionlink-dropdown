<?php

declare(strict_types=1);

namespace Drupal\actionlink_dropdown\ValueObject;

class CustomLink {
  protected string $title;
  protected string $routeName;
  protected array $routeParameters;

  public function __construct(string $title, string $routeName, array $routeParameters = []) {
    $this->title = $title;
    $this->routeName = $routeName;
    $this->routeParameters = $routeParameters;
  }

  public static function fromArray(array $values) {
    if (empty($values['title'])) {
      throw new \InvalidArgumentException('The values array must contain a value for the key "title"');
    }

    if (!is_string($values['title'])) {
      throw new \InvalidArgumentException('The value for the key "title" must be a string');
    }

    if (empty($values['route_name'])) {
      throw new \InvalidArgumentException('The values array must contain a value for the key "route_name"');
    }

    if (!is_string($values['route_name'])) {
      throw new \InvalidArgumentException('The value for the key "route_name" must be a string');
    }

    return new static($values['title'], $values['route_name'], (array) ($values['route_parameters'] ?? []));
  }

  public function getTitle(): string {
    return $this->title;
  }

  public function getRouteName(): string {
    return $this->routeName;
  }

  public function getRouteParameters(): array {
    return $this->routeParameters;
  }

}
