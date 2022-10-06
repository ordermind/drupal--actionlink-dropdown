<?php

namespace Drupal\actionlink_dropdown\Menu;

use Drupal\actionlink_dropdown\Enum\LocalActionWidgetTypeEnum;
use Drupal\actionlink_dropdown\Factory\OptionsFactory;
use Drupal\actionlink_dropdown\ValueObject\LocalActionOption;
use Drupal\Core\Menu\LocalActionManager as BaseManager;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Menu\LocalActionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;
use Drupal\Core\Session\AccountInterface;

class LocalActionManager extends BaseManager
{
  use StringTranslationTrait;

  protected OptionsFactory $optionsFactory;

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
    OptionsFactory $optionsFactory
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

    $this->optionsFactory = $optionsFactory;
  }


  /**
   * {@inheritdoc}
   */
  public function getActionsForRoute($route_appears)
  {
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
      $renderArray = $this->createRenderElement($plugin);
      if (!$renderArray) {
        continue;
      }

      $links[$plugin_id] = $renderArray;
      $cacheability->addCacheableDependency(AccessResult::allowed())->addCacheableDependency($plugin);
    }
    $cacheability->applyTo($links);

    return $links;
  }

  protected function createRenderElement(LocalActionInterface $plugin): array
  {
    $options = $plugin->getOptions($this->routeMatch);
    $type = $options['widget'] ?? NULL;
    if (
      $type === LocalActionWidgetTypeEnum::SELECT
      || $type === LocalActionWidgetTypeEnum::DETAILS
      || $type === LocalActionWidgetTypeEnum::DETAILS_PLUS_SELECT
    ) {
      return $this->createRenderElementForDropdownLink($plugin, $type);
    }

    return $this->createRenderElementForRegularLink($plugin);
  }

  protected function createRenderElementForRegularLink(LocalActionInterface $plugin): array
  {
    $route_name = $plugin->getRouteName();
    $route_parameters = $plugin->getRouteParameters($this->routeMatch);
    $access = $this->accessManager->checkNamedRoute($route_name, $route_parameters, $this->account, TRUE);

    return [
      '#theme' => 'menu_local_action',
      '#link' => [
        'title' => $this->getTitle($plugin),
        'url' => Url::fromRoute($route_name, $route_parameters),
        'localized_options' => $plugin->getOptions($this->routeMatch),
      ],
      '#access' => $access,
      '#weight' => $plugin->getWeight(),
    ];
  }

  protected function createRenderElementForDropdownLink(LocalActionInterface $plugin, string $type): array
  {
    $pluginOptions = $plugin->getOptions($this->routeMatch);

    $translationContext = $plugin->getPluginDefinition()['provider'];

    $options = $this->optionsFactory->createOptions($pluginOptions, $this->account, $translationContext);

    if ($options->isEmpty()) {
      return [];
    }

    if ($options->count() === 1) {
      /** @var LocalActionOption $firstOption */
      $firstOption = $options->first();
      return [
        '#theme' => 'menu_local_action',
        '#link' => [
          'title' => $firstOption->getTitle(),
          'url' => Url::fromRoute($firstOption->getRouteName(), $firstOption->getRouteParameters()),
          'localized_options' => $pluginOptions,
        ],
        '#weight' => $plugin->getWeight(),
      ];
    }

    return [
      '#theme' => "actionlink_dropdown_${type}",
      '#dropdown' => [
        'title' => $this->getTitle($plugin),
        'options' => $options->untype()->map(fn (LocalActionOption $option) => $option->toArray())->toArray(),
        'localized_options' => $pluginOptions,
      ],
    ];
  }
}
