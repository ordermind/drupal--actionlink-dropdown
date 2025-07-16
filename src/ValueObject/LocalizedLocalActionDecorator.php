<?php

declare(strict_types=1);

namespace Drupal\actionlink_dropdown\ValueObject;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Menu\LocalActionDefault;
use Ordermind\Helpers\Decorator\DecoratesObjectTrait;

/**
 * @mixin LocalActionDefault
 * @method LocalActionDefault getDecoratedObject
 * @property LocalActionDefault $decoratedObject
 */
class LocalizedLocalActionDecorator implements CacheableDependencyInterface {
  use DecoratesObjectTrait;

  protected string $localizedTitle;

  public function __construct(LocalActionDefault $plugin, string $localizedTitle) {
    $this->decoratedObject = $plugin;
    $this->localizedTitle = $localizedTitle;
  }

  public function getCacheContexts(): array {
    return $this->decoratedObject->getCacheContexts();
  }

  public function getCacheTags(): array {
    return $this->decoratedObject->getCacheTags();
  }

  public function getCacheMaxAge(): int {
    return $this->decoratedObject->getCacheMaxAge();
  }

  public function getLocalizedTitle(): string {
    return $this->localizedTitle;
  }

}
