<?php

declare(strict_types=1);

namespace Drupal\actionlink_dropdown\Render;

use Drupal\actionlink_dropdown\Collection\LocalActionOptionCollection;
use Drupal\actionlink_dropdown\Enum\LocalActionWidgetTypeEnum;
use Drupal\actionlink_dropdown\Factory\OptionsFactory;
use Drupal\actionlink_dropdown\ValueObject\LocalActionOption;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Access\AccessResultNeutral;
use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;

class LocalActionRenderer {
  protected OptionsFactory $optionsFactory;
  protected AccessManagerInterface $accessManager;

  public function __construct(
    OptionsFactory $optionsFactory,
    AccessManagerInterface $accessManager
  ) {
    $this->optionsFactory = $optionsFactory;
    $this->accessManager = $accessManager;
  }

  public function createRenderElement(
    LocalActionDefault $plugin,
    RouteMatchInterface $routeMatch,
    AccountInterface $account,
    string $title
  ): array {
    $pluginOptions = $plugin->getOptions($routeMatch);
    $type = $pluginOptions['widget'] ?? NULL;
    if (
      $type === LocalActionWidgetTypeEnum::SELECT
      || $type === LocalActionWidgetTypeEnum::DETAILS
      || $type === LocalActionWidgetTypeEnum::DETAILS_PLUS_SELECT
    ) {
      return $this->createRenderElementForDropdownLink($plugin, $type, $pluginOptions, $account, $title);
    }

    return $this->createRenderElementForRegularLink($plugin, $pluginOptions, $routeMatch, $account, $title);
  }

  protected function createRenderElementForRegularLink(
    LocalActionDefault $plugin,
    array $pluginOptions,
    RouteMatchInterface $routeMatch,
    AccountInterface $account,
    string $title
  ): array {
    $route_name = $plugin->getRouteName();
    $route_parameters = $plugin->getRouteParameters($routeMatch);
    $access = $this->accessManager->checkNamedRoute($route_name, $route_parameters, $account, TRUE);

    return [
      '#theme' => 'menu_local_action',
      '#link' => [
        'title' => $title,
        'url' => Url::fromRoute($route_name, $route_parameters),
        'localized_options' => $pluginOptions,
      ],
      '#access' => $access,
      '#weight' => $plugin->getWeight(),
    ];
  }

  protected function createRenderElementForDropdownLink(
    LocalActionDefault $plugin,
    string $type,
    array $pluginOptions,
    AccountInterface $account,
    string $title
  ): array {
    $translationContext = $plugin->getPluginDefinition()['provider'];

    $localActionOptions = $this->optionsFactory->createOptions($pluginOptions, $account, $translationContext);

    if ($localActionOptions->isEmpty()) {
      return [];
    }

    if ($localActionOptions->count() === 1) {
      /** @var \Drupal\actionlink_dropdown\ValueObject\LocalActionOption $firstOption */
      $firstOption = $localActionOptions->first();
      return [
        '#theme' => 'menu_local_action',
        '#link' => [
          // The title is already modified to use the fallback title prefix.
          'title' => $firstOption->getTitle(),
          'url' => Url::fromRoute($firstOption->getRouteName(), $firstOption->getRouteParameters()),
          'localized_options' => $pluginOptions,
        ],
        '#access' => $firstOption->getAccessResult(),
        '#weight' => $plugin->getWeight(),
      ];
    }

    return [
      '#theme' => "actionlink_dropdown_{$type}",
      '#dropdown' => [
        'title' => $title,
        'options' => $localActionOptions->untype()->map(fn (LocalActionOption $option) => $option->toArray())->toArray(),
        'localized_options' => $pluginOptions,
      ],
      '#access' => $this->getDropdownAccess($localActionOptions),
      '#weight' => $plugin->getWeight(),
    ];
  }

  /**
   * Creates a suitable access result based on the access results of
   * the options.
   */
  protected function getDropdownAccess(LocalActionOptionCollection $localActionOptions): AccessResultInterface {
    $accessHierarchy = [
      AccessResultForbidden::class,
      AccessResultNeutral::class,
      AccessResultAllowed::class,
    ];

    /** @var \Drupal\Core\Access\AccessResultInterface $highestAccessResult */
    $highestAccessResult = $localActionOptions->reduce(
      function (AccessResultInterface $previous, LocalActionOption $option) use ($accessHierarchy) {
        $accessResultClass = get_class($option->getAccessResult());
        $accessWeight = array_search($accessResultClass, $accessHierarchy, TRUE);
        if ($accessWeight === FALSE) {
          throw new \LogicException('The access result class "' . $accessResultClass . '" is unknown');
        }

        /** @var int $highestAccessWeight */
        $highestAccessWeight = array_search(get_class($previous), $accessHierarchy, TRUE);

        /** @var int $accessWeight */
        if ($accessWeight > $highestAccessWeight) {
          return $option->getAccessResult();
        }

        return $previous;
      },
      AccessResult::forbidden()
    );

    return $highestAccessResult;
  }

}
