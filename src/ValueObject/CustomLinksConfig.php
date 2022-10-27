<?php

declare(strict_types=1);

namespace Drupal\actionlink_dropdown\ValueObject;

use Drupal\actionlink_dropdown\Collection\CustomLinkCollection;

class CustomLinksConfig implements LocalActionOptionsConfigInterface {
  protected CustomLinkCollection $links;
  protected string $fallbackTitlePrefix;

  public function __construct(CustomLinkCollection $links, string $fallbackTitlePrefix) {
    $this->links = $links;
    $this->fallbackTitlePrefix = $fallbackTitlePrefix;
  }

  public static function fromArray(array $config): static {
    if (empty($config['custom_links'])) {
      throw new \InvalidArgumentException('The config array must include the key "custom_links" and not have an empty value');
    }
    if (empty($config['fallback_title_prefix'])) {
      throw new \InvalidArgumentException('The config array must include the key "fallback_title_prefix" which is used if there is only one link.');
    }

    return new static(
      new CustomLinkCollection(
        array_map(fn (array $linkData) => CustomLink::fromArray($linkData), $config['custom_links'])
      ),
      (string) $config['fallback_title_prefix']
    );
  }

  public function getLinks(): CustomLinkCollection {
    return $this->links;
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackTitlePrefix(): string {
    return $this->fallbackTitlePrefix;
  }
}
