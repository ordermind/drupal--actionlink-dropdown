<?php

declare(strict_types=1);

namespace Drupal\actionlink_dropdown\ValueObject;

use Drupal\actionlink_dropdown\Collection\CustomLinkCollection;

class CustomLinksConfig implements LocalActionOptionsConfigInterface {
  protected CustomLinkCollection $links;
  protected ?string $fallbackTitlePrefix;

  public function __construct(CustomLinkCollection $links, ?string $fallbackTitlePrefix = NULL) {
    $this->links = $links;
    $this->fallbackTitlePrefix = $fallbackTitlePrefix;
  }

  public static function fromArray(array $config): static {
    if (empty($config['custom_links'])) {
      throw new \InvalidArgumentException('The config array must contain a value for the key "custom_links"');
    }

    if (!is_array($config['custom_links'])) {
      throw new \InvalidArgumentException('The value for the key "custom_links" must be an array');
    }

    $links = new CustomLinkCollection(
      array_map(fn (array $linkData) => CustomLink::fromArray($linkData), $config['custom_links'])
    );

    if (empty($config['fallback_title_prefix'])) {
      return new static($links);
    }

    if (!is_string($config['fallback_title_prefix'])) {
      throw new \InvalidArgumentException('The value for the key "fallback_title_prefix" must be a string');
    }

    return new static($links, $config['fallback_title_prefix']);
  }

  public function getLinks(): CustomLinkCollection {
    return $this->links;
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackTitlePrefix(): ?string {
    return $this->fallbackTitlePrefix;
  }

}
