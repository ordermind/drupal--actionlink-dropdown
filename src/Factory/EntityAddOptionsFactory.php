<?php

namespace Drupal\actionlink_dropdown\Factory;

use Drupal\actionlink_dropdown\Collection\LocalActionOptionCollection;
use Drupal\actionlink_dropdown\Factory\Concerns\InsertsFallbackTitlePrefixForSingleOption;
use Drupal\actionlink_dropdown\ValueObject\EntityAddConfig;
use Drupal\actionlink_dropdown\ValueObject\LocalActionOption;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Session\AccountInterface;

class EntityAddOptionsFactory
{
    use InsertsFallbackTitlePrefixForSingleOption;

    protected EntityTypeManagerInterface $entityTypeManager;
    protected EntityTypeBundleInfoInterface $bundleInfo;
    protected AccessManagerInterface $accessManager;

    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        EntityTypeBundleInfoInterface $bundleInfo,
        AccessManagerInterface $accessManager
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->bundleInfo = $bundleInfo;
        $this->accessManager = $accessManager;
    }

    public function create(EntityAddConfig $config, AccountInterface $account, string $translationContext): LocalActionOptionCollection
    {
        $entityTypeId = $config->getEntityTypeId();
        $entityTypeDefinition = $this->entityTypeManager->getDefinition($entityTypeId);
        $bundles = $this->bundleInfo->getBundleInfo($entityTypeId);

        if (empty($bundles)) {
            return new LocalActionOptionCollection();
        }

        $options = (new LocalActionOptionCollection(
            array_map(fn (string $bundleId, array $bundleInfo) => new LocalActionOption(
                Markup::create($bundleInfo['label']),
                "entity.${entityTypeId}.add_form",
                [$entityTypeDefinition->getBundleEntityType() => $bundleId]
            ), array_keys($bundles), array_values($bundles))
        ))->filter(fn (LocalActionOption $option) => $this->accessManager->checkNamedRoute(
            $option->getRouteName(),
            $option->getRouteParameters(),
            $account,
            FALSE
        ));

        return $this->insertFallbackTitlePrefixForSingleOption(
            $options,
            $config->getFallbackTitlePrefix(),
            $translationContext
        );
    }
}
