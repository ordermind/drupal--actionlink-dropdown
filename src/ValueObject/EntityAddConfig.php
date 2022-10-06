<?php

namespace Drupal\actionlink_dropdown\ValueObject;

class EntityAddConfig implements LocalActionOptionsConfigInterface {
  protected string $entityTypeId;
  protected string $fallbackTitlePrefix;

  public function __construct(string $entityTypeId, string $fallbackTitlePrefix = 'Add') {
    $this->entityTypeId = $entityTypeId;
    $this->fallbackTitlePrefix = $fallbackTitlePrefix;
  }

  public static function fromArray(array $config): static {
    if (empty($config['entity_type'])) {
      throw new \InvalidArgumentException('The config array must include the key "entity_type"');
    }

    if (empty($config['fallback_title_prefix'])) {
      return new static((string) $config['entity_type']);
    }

    return new static((string) $config['entity_type'], (string) $config['fallback_title_prefix']);
  }

  public function getEntityTypeId(): string {
    return $this->entityTypeId;
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackTitlePrefix(): string {
    return $this->fallbackTitlePrefix;
  }

}
