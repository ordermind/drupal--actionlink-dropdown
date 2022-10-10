<?php

declare(strict_types=1);

namespace Drupal\actionlink_dropdown\Render;

use Drupal\actionlink_dropdown\Enum\LocalActionWidgetTypeEnum;
use Drupal\actionlink_dropdown\Factory\OptionsFactory;
use Drupal\actionlink_dropdown\ValueObject\LocalActionOption;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Menu\LocalActionInterface;
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
        LocalActionInterface $plugin,
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
        LocalActionInterface $plugin,
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
        LocalActionInterface $plugin,
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
                'title' => $title,
                'options' => $localActionOptions->untype()->map(fn (LocalActionOption $option) => $option->toArray())->toArray(),
                'localized_options' => $pluginOptions,
            ],
        ];
    }
}
