<?php

declare(strict_types=1);

namespace Drupal\actionlink_dropdown\ValueObject;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Access\AccessResultInterface;

class LocalActionOption {
  protected MarkupInterface $title;
  protected AccessResultInterface $accessResult;
  protected string $routeName;
  protected array $routeParameters;

  public function __construct(MarkupInterface $title, AccessResultInterface $accessResult, string $routeName, array $routeParameters = []) {
    $this->title = $title;
    $this->accessResult = $accessResult;
    $this->routeName = $routeName;
    $this->routeParameters = $routeParameters;
  }

  public function getTitle(): MarkupInterface {
    return $this->title;
  }

  public function getAccessResult(): AccessResultInterface {
    return $this->accessResult;
  }

  public function getRouteName(): string {
    return $this->routeName;
  }

  public function getRouteParameters(): array {
    return $this->routeParameters;
  }

  public function toArray(): array {
    return [
      'title' => $this->getTitle(),
      'access' => $this->getAccessResult(),
      'route_name' => $this->getRouteName(),
      'route_parameters' => $this->getRouteParameters(),
    ];
  }

}
