<?php

declare(strict_types=1);

namespace Drupal\actionlink_dropdown\ValueObject;

use Drupal\Core\Menu\LocalActionDefault;
use Ordermind\Helpers\Decorator\DecoratesObjectTrait;

/**
 * @mixin LocalActionDefault
 * @method LocalActionDefault getDecoratedObject
 */
class LocalizedLocalActionDecorator {
  use DecoratesObjectTrait;

  protected string $localizedTitle;

  public function __construct(LocalActionDefault $plugin, string $localizedTitle) {
    $this->decoratedObject = $plugin;
    $this->localizedTitle = $localizedTitle;
  }

  public function getLocalizedTitle(): string {
    return $this->localizedTitle;
  }

}
