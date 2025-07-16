<?php

declare(strict_types=1);

namespace Drupal\Tests\actionlink_dropdown\Unit\Factory;

use Drupal\actionlink_dropdown\Factory\CacheableLocalActionLinksFactory;
use Drupal\actionlink_dropdown\Render\LocalActionRenderer;
use Drupal\actionlink_dropdown\ValueObject\LocalizedLocalActionDecorator;
use Drupal\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\Render\Markup;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class CacheableLocalActionLinksFactoryTest extends UnitTestCase {
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

  public function testCreateFromLocalizedLocalActions(): void {
    $mockRouteMatch = $this->prophesize(RouteMatchInterface::class);
    $routeMatch = $mockRouteMatch->reveal();

    $mockAccount = $this->prophesize(AccountInterface::class);
    $account = $mockAccount->reveal();

    $localActions = [
      'do_nothing' => new LocalizedLocalActionDecorator(
        (function () {
          $mock = $this->prophesize(LocalActionDefault::class);
          $mock->getPluginId()->willReturn('do_nothing');
          $mock->getCacheContexts()->willReturn([]);
          $mock->getCacheTags()->willReturn([]);
          $mock->getCacheMaxAge()->willReturn(Cache::PERMANENT);
          return $mock->reveal();
        })(),
        'Test local action do nothing'
      ),
      'single_link' => new LocalizedLocalActionDecorator(
        (function () {
          $mock = $this->prophesize(LocalActionDefault::class);
          $mock->getPluginId()->willReturn('single_link');
          $mock->getCacheContexts()->willReturn([]);
          $mock->getCacheTags()->willReturn([]);
          $mock->getCacheMaxAge()->willReturn(Cache::PERMANENT);
          return $mock->reveal();
        })(),
        'Test local action single link'
      ),
      'dropdown' => new LocalizedLocalActionDecorator(
        (function () {
          $mock = $this->prophesize(LocalActionDefault::class);
          $mock->getPluginId()->willReturn('dropdown');
          $mock->getCacheContexts()->willReturn([]);
          $mock->getCacheTags()->willReturn([]);
          $mock->getCacheMaxAge()->willReturn(Cache::PERMANENT);
          return $mock->reveal();
        })(),
        'Test local action dropdown'
      ),
    ];

    $mockRenderer = $this->prophesize(LocalActionRenderer::class);
    $mockRenderer
      ->createRenderElement(
        $localActions['do_nothing']->getDecoratedObject(),
        $routeMatch,
        $account,
        $localActions['do_nothing']->getLocalizedTitle()
      )
      ->willReturn([]);

    $mockRenderer
      ->createRenderElement(
        $localActions['single_link']->getDecoratedObject(),
        $routeMatch,
        $account,
        $localActions['single_link']->getLocalizedTitle()
      )
      ->will(
        function () use ($localActions) {
          return [
            '#theme' => 'menu_local_action',
            '#link' => [
              'title' => $localActions['single_link']->getLocalizedTitle(),
              'url' => Url::fromRoute(
                'node.add',
                ['node_type' => 'bundle_1']
              ),
              'localized_options' => [],
            ],
            '#access' => AccessResult::allowed()->addCacheContexts(['user.permissions']),
            '#weight' => 5,
          ];
        }
      );

    $mockRenderer
      ->createRenderElement(
        $localActions['dropdown']->getDecoratedObject(),
        $routeMatch,
        $account,
        $localActions['dropdown']->getLocalizedTitle()
      )
      ->will(
        function () use ($localActions) {
          return [
            '#theme' => 'actionlink_dropdown_details',
            '#dropdown' => [
              'title' => $localActions['dropdown']->getLocalizedTitle(),
              'options' => [
                [
                  'title' => Markup::create('Bundle 1'),
                  'route_name' => 'node.add',
                  'route_parameters' => [
                    'node_type' => 'bundle_1',
                  ],
                  'access' => AccessResult::forbidden()->addCacheContexts(['user.permissions']),
                ],
                [
                  'title' => Markup::create('Bundle 2'),
                  'route_name' => 'node.add',
                  'route_parameters' => [
                    'node_type' => 'bundle_2',
                  ],
                  'access' => AccessResult::allowed()->addCacheContexts(['user.permissions']),
                ],
              ],
              'localized_options' => [
                'widget' => 'details',
                'links' => 'entity_add',
                'entity_type' => 'node',
                'query' => [
                  'destination' => '/',
                ],
              ],
            ],
            '#access' => AccessResult::allowed()->addCacheContexts(['user.permissions']),
            '#weight' => 5,
          ];
        }
      );
    $renderer = $mockRenderer->reveal();

    $factory = new CacheableLocalActionLinksFactory($renderer);

    $expected = [
      'single_link' => $renderer->createRenderElement(
        $localActions['single_link']->getDecoratedObject(),
        $routeMatch,
        $account,
        $localActions['single_link']->getLocalizedTitle()
      ),
      'dropdown' => $renderer->createRenderElement(
        $localActions['dropdown']->getDecoratedObject(),
        $routeMatch,
        $account,
        $localActions['dropdown']->getLocalizedTitle()
      ),
      '#cache' => [
        'contexts' => [
          'route',
          'user.permissions',
        ],
        'tags' => [],
        'max-age' => -1,
      ],
    ];

    $this->assertEquals($expected, $factory->createFromLocalizedLocalActions($routeMatch, $account, ...$localActions));
  }

}
