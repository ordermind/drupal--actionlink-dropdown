<?php

declare(strict_types=1);

namespace Drupal\Tests\actionlink_dropdown\Unit\Render;

use Drupal\actionlink_dropdown\Collection\LocalActionOptionCollection;
use Drupal\actionlink_dropdown\Factory\OptionsFactory;
use Drupal\actionlink_dropdown\Render\LocalActionRenderer;
use Drupal\actionlink_dropdown\ValueObject\LocalActionOption;
use Drupal\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Access\AccessResultNeutral;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Render\Markup;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\menu_ui\Plugin\Menu\LocalAction\MenuLinkAdd;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class LocalActionRendererTest extends UnitTestCase {
  use ProphecyTrait;

  protected function setUp(): void {
    parent::setUp();

    $mockCacheContextManager = $this->prophesize(CacheContextsManager::class);
    $mockCacheContextManager->assertValidTokens(Argument::cetera())->willReturn(TRUE);
    $cacheContextManager = $mockCacheContextManager->reveal();

    $mockContainer = $this->prophesize(ContainerInterface::class);
    $mockContainer->get('cache_contexts_manager')->willReturn($cacheContextManager);
    $container = $mockContainer->reveal();

    \Drupal::setContainer($container);
  }

  /**
   * @dataProvider accessResultProvider
   */
  public function testRegularLink(string $expectedAccessResultClass): void {
    $expectedAccessResult = (new $expectedAccessResultClass())->addCacheContexts(['user.permissions']);

    $mockOptionsFactory = $this->prophesize(OptionsFactory::class);
    $optionsFactory = $mockOptionsFactory->reveal();

    $mockAccessManager = $this->prophesize(AccessManagerInterface::class);
    $mockAccessManager->checkNamedRoute(Argument::cetera())->willReturn($expectedAccessResult);
    $accessManager = $mockAccessManager->reveal();

    $mockLocalAction = $this->prophesize(MenuLinkAdd::class);
    $mockLocalAction->getRouteName()->willReturn('user.admin_index');
    $mockLocalAction->getRouteParameters(Argument::cetera())->willReturn([]);
    $mockLocalAction->getOptions(Argument::cetera())->willReturn([
      'query' => [
        'destination' => '/',
      ],
    ]);
    $mockLocalAction->getWeight()->willReturn(NULL);
    $localAction = $mockLocalAction->reveal();

    $mockRouteMatch = $this->prophesize(RouteMatchInterface::class);
    $routeMatch = $mockRouteMatch->reveal();

    $mockAccount = $this->prophesize(AccountInterface::class);
    $account = $mockAccount->reveal();

    $renderer = new LocalActionRenderer($optionsFactory, $accessManager);

    $renderElement = $renderer->createRenderElement(
          $localAction,
          $routeMatch,
          $account,
          'Test link'
      );

    $expected = [
      '#theme' => 'menu_local_action',
      '#link' => [
        'title' => 'Test link',
        'url' => Url::fromRoute($localAction->getRouteName(), $localAction->getRouteParameters($routeMatch)),
        'localized_options' => [
          'query' => [
            'destination' => '/',
          ],
        ],
      ],
      '#access' => $expectedAccessResult,
      '#weight' => NULL,
    ];

    $this->assertEquals($expected, $renderElement);
  }

  public function testSingleDropdownLink(): void {
    $pluginDefinition = ['provider' => 'test_provider'];

    $pluginOptions = [
      'widget' => 'details',
      'links' => 'entity_add',
      'entity_type' => 'tengstrom_demo_content',
      'fallback_title_prefix' => 'Add',
          // The query part is added by the MenuLinkAdd but since we are mocking the local action we need to add it manually.
      'query' => [
        'destination' => '/',
      ],
    ];

    $mockLocalAction = $this->prophesize(MenuLinkAdd::class);
    $mockLocalAction->getPluginDefinition()->willReturn($pluginDefinition);
    $mockLocalAction->getRouteName()->willReturn('user.admin_index');
    $mockLocalAction->getRouteParameters(Argument::cetera())->willReturn([]);
    $mockLocalAction->getOptions(Argument::cetera())->willReturn($pluginOptions);
    $mockLocalAction->getWeight()->willReturn(5);
    $localAction = $mockLocalAction->reveal();

    $mockAccount = $this->prophesize(AccountInterface::class);
    $account = $mockAccount->reveal();

    $localActionOptions = new LocalActionOptionCollection([
      new LocalActionOption(
    // Here we simulate that the title has already been merged with the fallback title prefix.
              Markup::create('Add Bundle 1'),
              AccessResult::forbidden()->addCacheContexts(['user.permissions']),
              'node.add',
              ['node_type' => 'bundle_1']
      ),
    ]);

    $mockOptionsFactory = $this->prophesize(OptionsFactory::class);
    $mockOptionsFactory->createOptions($pluginOptions, $account, $pluginDefinition['provider'])->willReturn($localActionOptions);
    $optionsFactory = $mockOptionsFactory->reveal();

    $mockAccessManager = $this->prophesize(AccessManagerInterface::class);
    $accessManager = $mockAccessManager->reveal();

    $mockRouteMatch = $this->prophesize(RouteMatchInterface::class);
    $routeMatch = $mockRouteMatch->reveal();

    $renderer = new LocalActionRenderer($optionsFactory, $accessManager);

    $renderElement = $renderer->createRenderElement(
          $localAction,
          $routeMatch,
          $account,
          'Add node'
      );

    $expected = [
      '#theme' => 'menu_local_action',
      '#link' => [
        'title' => 'Add Bundle 1',
        'url' => Url::fromRoute(
                  $localActionOptions->get(0)->getRouteName(),
                  $localActionOptions->get(0)->getRouteParameters()
        ),
        'localized_options' => $pluginOptions,
      ],
      '#access' => AccessResult::forbidden()->addCacheContexts(['user.permissions']),
      '#weight' => 5,
    ];

    $this->assertEquals($expected, $renderElement);
  }

  public function testMultipleDropdownLinks(): void {
    $pluginDefinition = ['provider' => 'test_provider'];

    $pluginOptions = [
      'widget' => 'details',
      'links' => 'entity_add',
      'entity_type' => 'tengstrom_demo_content',
          // The query part is added by the MenuLinkAdd but since we are mocking the local action we need to add it manually.
      'query' => [
        'destination' => '/',
      ],
    ];

    $mockLocalAction = $this->prophesize(MenuLinkAdd::class);
    $mockLocalAction->getPluginDefinition()->willReturn($pluginDefinition);
    $mockLocalAction->getRouteName()->willReturn('user.admin_index');
    $mockLocalAction->getRouteParameters(Argument::cetera())->willReturn([]);
    $mockLocalAction->getOptions(Argument::cetera())->willReturn($pluginOptions);
    $mockLocalAction->getWeight()->willReturn(5);
    $localAction = $mockLocalAction->reveal();

    $mockAccount = $this->prophesize(AccountInterface::class);
    $account = $mockAccount->reveal();

    $localActionOptions = new LocalActionOptionCollection([
      new LocalActionOption(
              Markup::create('Bundle 1'),
              AccessResult::forbidden()->addCacheContexts(['user.permissions']),
              'node.add',
              ['node_type' => 'bundle_1']
      ),
      new LocalActionOption(
              Markup::create('Bundle 2'),
              AccessResult::neutral()->addCacheContexts(['user.permissions']),
              'node.add',
              ['node_type' => 'bundle_2']
      ),
    ]);

    $mockOptionsFactory = $this->prophesize(OptionsFactory::class);
    $mockOptionsFactory->createOptions($pluginOptions, $account, $pluginDefinition['provider'])->willReturn($localActionOptions);
    $optionsFactory = $mockOptionsFactory->reveal();

    $mockAccessManager = $this->prophesize(AccessManagerInterface::class);
    $accessManager = $mockAccessManager->reveal();

    $mockRouteMatch = $this->prophesize(RouteMatchInterface::class);
    $routeMatch = $mockRouteMatch->reveal();

    $renderer = new LocalActionRenderer($optionsFactory, $accessManager);

    $renderElement = $renderer->createRenderElement(
          $localAction,
          $routeMatch,
          $account,
          'Add node'
      );

    $expected = [
      '#theme' => 'actionlink_dropdown_details',
      '#dropdown' => [
        'title' => 'Add node',
        'options' => [
                  [
                    'title' => $localActionOptions->get(0)->getTitle(),
                    'access' => $localActionOptions->get(0)->getAccessResult(),
                    'route_name' => $localActionOptions->get(0)->getRouteName(),
                    'route_parameters' => $localActionOptions->get(0)->getRouteParameters(),
                  ],
                  [
                    'title' => $localActionOptions->get(1)->getTitle(),
                    'access' => $localActionOptions->get(1)->getAccessResult(),
                    'route_name' => $localActionOptions->get(1)->getRouteName(),
                    'route_parameters' => $localActionOptions->get(1)->getRouteParameters(),
                  ],
        ],
        'localized_options' => $pluginOptions,
      ],
      '#access' => AccessResult::neutral()->addCacheContexts(['user.permissions']),
      '#weight' => 5,
    ];

    $this->assertEquals($expected, $renderElement);
  }

  public function accessResultProvider(): array {
    return [
          [AccessResultForbidden::class],
          [AccessResultNeutral::class],
          [AccessResultAllowed::class],
    ];
  }

}
