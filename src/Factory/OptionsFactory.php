<?php

namespace Drupal\actionlink_dropdown\Factory;

use Drupal\actionlink_dropdown\Collection\CustomLinkCollection;
use Drupal\actionlink_dropdown\Collection\LocalActionOptionCollection;
use Drupal\actionlink_dropdown\Enum\LocalActionLinksTypeEnum;
use Drupal\actionlink_dropdown\Factory\CustomOptionsFactory;
use Drupal\actionlink_dropdown\ValueObject\CustomLink;
use Drupal\actionlink_dropdown\ValueObject\CustomLinksConfig;
use Drupal\actionlink_dropdown\ValueObject\EntityAddConfig;
use Drupal\Core\Session\AccountInterface;
use InvalidArgumentException;

class OptionsFactory
{
    protected CustomOptionsFactory $customOptionsFactory;
    protected EntityAddOptionsFactory $entityAddOptionsFactory;

    public function __construct(CustomOptionsFactory $customOptionsFactory, EntityAddOptionsFactory $entityAddOptionsFactory)
    {
        $this->customOptionsFactory = $customOptionsFactory;
        $this->entityAddOptionsFactory = $entityAddOptionsFactory;
    }

    public function createOptions(array $config, AccountInterface $account, string $translationContext): LocalActionOptionCollection
    {
        if (empty($config['links'])) {
            throw new InvalidArgumentException('The config array must include the key "links"');
        }

        if ($config['links'] === LocalActionLinksTypeEnum::CUSTOM) {
            return $this->createCustomLinks($config, $account, $translationContext);
        }

        if ($config['links'] === LocalActionLinksTypeEnum::ENTITY_ADD) {
            return $this->createEntityAddLinks($config, $account, $translationContext);
        }

        throw new InvalidArgumentException('The value "' . print_r($config['links'], true) . '" is not supported for the "links" key');
    }

    protected function createCustomLinks(array $config, AccountInterface $account, string $translationContext): LocalActionOptionCollection
    {
        if (empty($config['custom_links'])) {
            throw new InvalidArgumentException('If custom links are used, the config array must include the key "custom_links"');
        }
        if (empty($config['fallback_title_prefix'])) {
            throw new InvalidArgumentException('If custom links are used, the config array must include the key "fallback_title_prefix" which is used if there is only one link.');
        }

        $customLinksConfig = new CustomLinksConfig(
            new CustomLinkCollection(
                array_map(fn (array $linkData) => CustomLink::fromArray($linkData), $config['custom_links'])
            ),
            (string) $config['fallback_title_prefix']
        );

        return $this->customOptionsFactory->create($customLinksConfig, $account, $translationContext);
    }

    protected function createEntityAddLinks(array $config, AccountInterface $account, string $translationContext): LocalActionOptionCollection
    {
        if (empty($config['entity_type'])) {
            throw new InvalidArgumentException('If entity add links are used, the config array must include the key "entity_type"');
        }

        $entityAddConfig = new EntityAddConfig((string) $config['entity_type']);

        return $this->entityAddOptionsFactory->create($entityAddConfig, $account, $translationContext);
    }
}
