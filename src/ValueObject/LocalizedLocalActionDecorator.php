<?php

declare(strict_types=1);

namespace Drupal\actionlink_dropdown\ValueObject;

use Drupal\actionlink_dropdown\Concerns\DecoratesObject;
use Drupal\Core\Menu\LocalActionDefault;

/**
 * @mixin LocalActionDefault
 * @method LocalActionDefault getDecoratedObject
 */
class LocalizedLocalActionDecorator {
    use DecoratesObject;

    protected LocalActionDefault $plugin;
    protected string $localizedTitle;

    public function __construct(LocalActionDefault $plugin, string $localizedTitle) {
        $this->decoratedObject = $plugin;
        $this->localizedTitle = $localizedTitle;
    }

    public function getLocalizedTitle(): string {
        return $this->localizedTitle;
    }
}
