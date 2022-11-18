<?php

declare(strict_types=1);

namespace Drupal\actionlink_dropdown\ValueObject;

use Drupal\actionlink_dropdown\Concerns\DecoratesObject;
use Drupal\Core\Menu\LocalActionDefault;

class LocalizedLocalActionDecorator extends LocalActionDefault {
    use DecoratesObject;

    protected string $identifier;
    protected LocalActionDefault $plugin;
    protected string $localizedTitle;

    public function __construct(string $identifier, LocalActionDefault $plugin, string $localizedTitle) {
        $this->identifier = $identifier;
        $this->plugin = $plugin;
        $this->localizedTitle = $localizedTitle;
    }

    public function getIdentifier(): string {
        return $this->identifier;
    }

    public function getLocalizedTitle(): string {
        return $this->localizedTitle;
    }
}
