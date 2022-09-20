<?php

namespace Drupal\actionlink_dropdown\Menu;

use Drupal\actionlink_dropdown\Enum\LocalActionDropdownTypeEnum;
use Drupal\Core\Menu\LocalActionManager as BaseManager;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Menu\LocalActionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

class LocalActionManager extends BaseManager {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
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
      $renderArray = $this->createRenderElement($plugin);
      if (!$renderArray) {
        continue;
      }

      $links[$plugin_id] = $renderArray;
      $cacheability->addCacheableDependency($renderArray['#access'])->addCacheableDependency($plugin);
    }
    $cacheability->applyTo($links);

    return $links;
  }

  protected function createRenderElement(LocalActionInterface $plugin): array {
    $options = $plugin->getOptions($this->routeMatch);
    $type = $options['type'] ?? NULL;
    if (
      $type === LocalActionDropdownTypeEnum::SELECT
      || $type === LocalActionDropdownTypeEnum::DETAILS
      || $type === LocalActionDropdownTypeEnum::DETAILS_PLUS_SELECT
    ) {
      return $this->createRenderElementForDropdownLink($plugin, $type);
    }

    return $this->createRenderElementForRegularLink($plugin);
  }

  protected function createRenderElementForRegularLink(LocalActionInterface $plugin): array {
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

  protected function createRenderElementForDropdownLink(LocalActionInterface $plugin, string $type): array {
    $pluginOptions = $plugin->getOptions($this->routeMatch);

    $translationContext = $plugin->getPluginDefinition()['provider'];

    $allowedOptions = array_filter(
      array_map(
        function (array $option) {
          $accessResult = $this->accessManager->checkNamedRoute(
            $option['route_name'],
            $option['route_parameters'] ?? [],
            $this->account,
            TRUE
          );

          if (!$accessResult->isAllowed()) {
            return NULL;
          }

          return $option + ['access_result' => $accessResult];
        },
        $pluginOptions['dropdown_options']
      )
    );

    if (empty($allowedOptions)) {
      return [];
    }

    $firstOption = reset($allowedOptions);

    if (count($allowedOptions) == 1) {
      $route_name = $firstOption['route_name'];
      $route_parameters = $firstOption['route_parameters'] ?? [];
      return [
        '#theme' => 'menu_local_action',
        '#link' => [
          'title' => $this->t($pluginOptions['fallback_title_prefix'] . ' @option', ['@option' => $this->t($firstOption['title'])], ['context' => $translationContext]),
          'url' => Url::fromRoute($route_name, $route_parameters),
          'localized_options' => $plugin->getOptions($this->routeMatch),
        ],
        '#access' => $firstOption['access_result'],
        '#weight' => $plugin->getWeight(),
      ];
    }

    return [
      '#theme' => "actionlink_dropdown_${type}",
      '#dropdown' => [
        'title' => $this->getTitle($plugin),
        'options' => array_map(
          function (array $option) use($translationContext) {
            unset($option['access_result']);

            $option['title'] = $this->t($option['title'], [], ['context' => $translationContext]);

            return $option;
          },
          $allowedOptions
        ),
        'localized_options' => $plugin->getOptions($this->routeMatch),
      ],
      '#access' => $firstOption['access_result'],
    ];
  }

}
