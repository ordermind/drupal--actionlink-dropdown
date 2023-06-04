<?php

declare(strict_types=1);

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

class EntityAddOptionsFactory {
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

  public function create(EntityAddConfig $config, AccountInterface $account, string $translationContext): LocalActionOptionCollection {
    $entityTypeId = $config->getEntityTypeId();
    $entityTypeDefinition = $this->entityTypeManager->getDefinition($entityTypeId);
    $bundleEntityTypeId = $entityTypeDefinition->getBundleEntityType();
    if (!$bundleEntityTypeId) {
      throw new \LogicException("The entity type \"{$entityTypeId}\" does not support bundles. Entity types without bundles are not supported for entity add links.");
    }

    $bundles = $this->bundleInfo->getBundleInfo($entityTypeId);
    if (empty($bundles)) {
      return new LocalActionOptionCollection();
    }

    $options = (new LocalActionOptionCollection(
      array_map(function (string $bundleId, array $bundleInfo) use ($entityTypeId, $bundleEntityTypeId, $account) {
        $routeName = $this->getAddEntityRoute($entityTypeId);
        $routeParameters = [$bundleEntityTypeId => $bundleId];
        $access = $this->accessManager->checkNamedRoute(
          $routeName,
          $routeParameters,
          $account,
          TRUE
        );

        return new LocalActionOption(
          Markup::create($bundleInfo['label']),
          $access,
          $routeName,
          $routeParameters
        );
      }, array_keys($bundles), array_values($bundles))
    ));

    return $this->insertFallbackTitlePrefixForSingleOption(
      $options,
      $config->getFallbackTitlePrefix(),
      $translationContext
    );
  }

  protected function getAddEntityRoute(string $entityTypeId): string {
    if ($entityTypeId === 'node') {
      return 'node.add';
    }

    return "entity.{$entityTypeId}.add_form";
  }

}
