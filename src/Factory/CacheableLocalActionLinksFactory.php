<?php

declare(strict_types=1);

namespace Drupal\actionlink_dropdown\Factory;

use Drupal\actionlink_dropdown\Render\LocalActionRenderer;
use Drupal\actionlink_dropdown\ValueObject\LocalizedLocalActionDecorator;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Creates local action links and adds cacheability metadata to them.
 */
class CacheableLocalActionLinksFactory {
  protected LocalActionRenderer $renderer;

  public function __construct(LocalActionRenderer $renderer) {
    $this->renderer = $renderer;
  }

  public function createFromLocalizedLocalActions(
    RouteMatchInterface $routeMatch,
    AccountInterface $account,
    LocalizedLocalActionDecorator ...$localActions,
  ): array {
    $links = [];
    $cacheability = new CacheableMetadata();
    $cacheability->addCacheContexts(['route']);
    foreach ($localActions as $decoratedPlugin) {
      $renderArray = $this->renderer->createRenderElement(
        $decoratedPlugin->getDecoratedObject(),
        $routeMatch,
        $account,
        $decoratedPlugin->getLocalizedTitle()
      );
      if (!$renderArray) {
        continue;
      }

      $links[$decoratedPlugin->getPluginId()] = $renderArray;

      $this->addAccessCaching($renderArray, $cacheability);
      $cacheability->addCacheableDependency($decoratedPlugin);
    }
    $cacheability->applyTo($links);

    return $links;
  }

  /**
   * Adds access caching metadata not only for regular links but
   * also for dropdown links.
   */
  protected function addAccessCaching(array &$renderArray, CacheableMetadata $cacheability): void {
    $cacheability->addCacheableDependency($renderArray['#access']);

    if (empty($renderArray['#dropdown'])) {
      return;
    }

    foreach ($renderArray['#dropdown']['options'] as $option) {
      $cacheability->addCacheableDependency($option['access']);
    }
  }

}
