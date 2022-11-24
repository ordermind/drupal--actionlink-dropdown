<?php

declare(strict_types=1);

namespace Drupal\actionlink_dropdown\Menu;

use Drupal\actionlink_dropdown\Factory\CacheableLocalActionLinksFactory;
use Drupal\actionlink_dropdown\ValueObject\LocalizedLocalActionDecorator;
use Drupal\Core\Menu\LocalActionManager as BaseManager;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Menu\LocalActionInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;
use Drupal\Core\Session\AccountInterface;

class LocalActionManager extends BaseManager {
  use StringTranslationTrait;

  protected CacheableLocalActionLinksFactory $actionLinksFactory;

  public function __construct(
    ArgumentResolverInterface $argumentResolver,
    RequestStack $requestStack,
    RouteMatchInterface $routeMatch,
    RouteProviderInterface $routeProvider,
    ModuleHandlerInterface $moduleHandler,
    CacheBackendInterface $cacheBackend,
    LanguageManagerInterface $languageManager,
    AccessManagerInterface $accessManager,
    AccountInterface $account,
    CacheableLocalActionLinksFactory $actionLinksFactory
  ) {
    parent::__construct(
      $argumentResolver,
      $requestStack,
      $routeMatch,
      $routeProvider,
      $moduleHandler,
      $cacheBackend,
      $languageManager,
      $accessManager,
      $account
    );

    $this->actionLinksFactory = $actionLinksFactory;
  }

  /**
   * {@inheritdoc}
   * 
   * Changes the parent method to delegate the creation of the render arrays to an external service.
   */
  public function getActionsForRoute($route_appears) {
    if (!isset($this->instances[$route_appears])) {
      $route_names = [];
      $this->instances[$route_appears] = [];
      // @todo optimize this lookup by compiling or caching.
      foreach ($this->getDefinitions() as $plugin_id => $action_info) {
        if (in_array($route_appears, $action_info['appears_on'])) {
          $plugin = $this->createInstance($plugin_id);
          $route_names[] = $plugin->getRouteName();
          $this->instances[$route_appears][$plugin_id] = $plugin;
        }
      }
      // Pre-fetch all the action route objects. This reduces the number of SQL
      // queries that would otherwise be triggered by the access manager.
      if (!empty($route_names)) {
        $this->routeProvider->getRoutesByNames($route_names);
      }
    }

    return $this->createRenderArrays($route_appears);
  }

  protected function createRenderArrays(string $route_appears): array {
    /** @var LocalActionInterface[] $relevantInstances */
    $relevantInstances = $this->instances[$route_appears];

    /** @var LocalizedLocalActionDecorator[] $localizedLocalActions */
    $localizedLocalActions = array_map(
      function (LocalActionInterface $plugin): LocalizedLocalActionDecorator {
        return new LocalizedLocalActionDecorator($plugin, $this->getTitle($plugin));
      },
      array_values($relevantInstances)
    );

    return $this->actionLinksFactory->createFromLocalizedLocalActions(
      $this->routeMatch,
      $this->account,
      ...$localizedLocalActions
    );
  }
}
