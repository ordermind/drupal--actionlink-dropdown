<?php

declare(strict_types=1);

namespace Drupal\actionlink_dropdown\Menu;

use Drupal\actionlink_dropdown\Factory\OptionsFactory;
use Drupal\actionlink_dropdown\Render\LocalActionRenderer;
use Drupal\Core\Menu\LocalActionManager as BaseManager;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;
use Drupal\Core\Session\AccountInterface;

class LocalActionManager extends BaseManager {
  use StringTranslationTrait;

  protected LocalActionRenderer $renderer;

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
    LocalActionRenderer $renderer
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

    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   * 
   * Based on Drupal\Core\Menu\LocalActionManager::getActionsForRoute(). I have simplified the caching a little bit which could potentially
   * cause issues for different roles.
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
    $links = [];
    $cacheability = new CacheableMetadata();
    $cacheability->addCacheContexts(['route']);
    /** @var \Drupal\Core\Menu\LocalActionInterface $plugin */
    foreach ($this->instances[$route_appears] as $plugin_id => $plugin) {
      $renderArray = $this->renderer->createRenderElement($plugin, $this->routeMatch, $this->account, $this->getTitle($plugin));
      if (!$renderArray) {
        continue;
      }

      $links[$plugin_id] = $renderArray;
      $cacheability->addCacheableDependency($plugin);
    }
    $cacheability->applyTo($links);

    return $links;
  }
}
