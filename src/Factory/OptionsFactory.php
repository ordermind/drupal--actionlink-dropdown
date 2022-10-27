<?php

declare(strict_types=1);

namespace Drupal\actionlink_dropdown\Factory;

use Drupal\actionlink_dropdown\Collection\LocalActionOptionCollection;
use Drupal\actionlink_dropdown\Enum\LocalActionLinksTypeEnum;
use Drupal\actionlink_dropdown\ValueObject\CustomLinksConfig;
use Drupal\actionlink_dropdown\ValueObject\EntityAddConfig;
use Drupal\Core\Session\AccountInterface;

class OptionsFactory {
  protected CustomOptionsFactory $customOptionsFactory;
  protected EntityAddOptionsFactory $entityAddOptionsFactory;

  public function __construct(CustomOptionsFactory $customOptionsFactory, EntityAddOptionsFactory $entityAddOptionsFactory) {
    $this->customOptionsFactory = $customOptionsFactory;
    $this->entityAddOptionsFactory = $entityAddOptionsFactory;
  }

  public function createOptions(array $config, AccountInterface $account, string $translationContext): LocalActionOptionCollection {
    if (empty($config['links'])) {
      throw new \InvalidArgumentException('The config array must include the key "links"');
    }

    if ($config['links'] === LocalActionLinksTypeEnum::CUSTOM) {
      return $this->createCustomLinks($config, $account, $translationContext);
    }

    if ($config['links'] === LocalActionLinksTypeEnum::ENTITY_ADD) {
      return $this->createEntityAddLinks($config, $account, $translationContext);
    }

    throw new \InvalidArgumentException('The value "' . print_r($config['links'], TRUE) . '" is not supported for the "links" key');
  }

  protected function createCustomLinks(array $config, AccountInterface $account, string $translationContext): LocalActionOptionCollection {
    $customLinksConfig = CustomLinksConfig::fromArray($config);

    return $this->customOptionsFactory->create($customLinksConfig, $account, $translationContext);
  }

  protected function createEntityAddLinks(array $config, AccountInterface $account, string $translationContext): LocalActionOptionCollection {
    $entityAddConfig = EntityAddConfig::fromArray($config);

    return $this->entityAddOptionsFactory->create($entityAddConfig, $account, $translationContext);
  }
}
