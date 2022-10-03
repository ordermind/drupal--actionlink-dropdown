<?php

namespace Drupal\actionlink_dropdown\ValueObject;

use Drupal\actionlink_dropdown\Collection\CustomOptionCollection;

class CustomOptionsConfig
{
    protected CustomOptionCollection $options;
    protected string $fallbackTitle;

    public function __construct(CustomOptionCollection $options, string $fallbackTitle)
    {
        $this->options = $options;
        $this->fallbackTitle = $fallbackTitle;
    }

    public function getOptions(): CustomOptionCollection
    {
        return $this->options;
    }

    public function getFallbackTitle(): string
    {
        return $this->fallbackTitle;
    }
}
