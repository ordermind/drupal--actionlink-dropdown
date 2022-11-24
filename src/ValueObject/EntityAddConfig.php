<?php

declare(strict_types=1);

namespace Drupal\actionlink_dropdown\ValueObject;

class EntityAddConfig implements LocalActionOptionsConfigInterface {
  protected string $entityTypeId;
  protected ?string $fallbackTitlePrefix;

  public function __construct(string $entityTypeId, ?string $fallbackTitlePrefix = 'Add') {
    $this->entityTypeId = $entityTypeId;
    $this->fallbackTitlePrefix = $fallbackTitlePrefix;
  }

  public static function fromArray(array $config): static {
    if (empty($config['entity_type'])) {
      throw new \InvalidArgumentException('The config array must contain a value for the key "entity_type"');
    }

    if (!is_string($config['entity_type'])) {
      throw new \InvalidArgumentException('The value for the key "entity_type" must be a string');
    }

    if (empty($config['fallback_title_prefix'])) {
      return new static($config['entity_type']);
    }

    if (!is_string($config['fallback_title_prefix'])) {
      throw new \InvalidArgumentException('The value for the key "fallback_title_prefix" must be a string');
    }

    return new static($config['entity_type'], $config['fallback_title_prefix']);
  }

  public function getEntityTypeId(): string {
    return $this->entityTypeId;
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackTitlePrefix(): ?string {
    return $this->fallbackTitlePrefix;
  }

}
