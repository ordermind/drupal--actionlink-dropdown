<?php

namespace Drupal\actionlink_dropdown\ValueObject;

use Drupal\actionlink_dropdown\Collection\CustomLinkCollection;

class CustomLinksConfig
{
    protected CustomLinkCollection $links;
    protected string $fallbackTitlePrefix;

    public function __construct(CustomLinkCollection $links, string $fallbackTitlePrefix)
    {
        $this->links = $links;
        $this->fallbackTitlePrefix = $fallbackTitlePrefix;
    }

    public function getLinks(): CustomLinkCollection
    {
        return $this->links;
    }

    public function getFallbackTitlePrefix(): string
    {
        return $this->fallbackTitlePrefix;
    }
}
