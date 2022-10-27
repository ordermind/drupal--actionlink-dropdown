<?php

declare(strict_types=1);

namespace Drupal\Tests\actionlink_dropdown\Unit;

use Drupal\actionlink_dropdown\Factory\OptionsFactory;
use Drupal\actionlink_dropdown\Render\LocalActionRenderer;
use Drupal\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Access\AccessResultNeutral;
use Drupal\Core\Cache\Context\CacheContextsManager;
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
     * @dataProvider regularLinkProvider
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
            '#weight' => null,
        ];

        $this->assertEquals($expected, $renderElement);
    }

    public function regularLinkProvider(): array {
        return [
            [AccessResultForbidden::class],
            [AccessResultNeutral::class],
            [AccessResultAllowed::class],
        ];
    }
}
