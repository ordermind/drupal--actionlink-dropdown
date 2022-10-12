<?php

declare(strict_types=1);

namespace Drupal\Tests\actionlink_dropdown\Kernel;

use Drupal\actionlink_dropdown\Render\LocalActionRenderer;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Http\RequestStack;
use Drupal\Core\Render\Markup;
use Drupal\Core\Routing\RedirectDestination;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Url;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\menu_ui\Plugin\Menu\LocalAction\MenuLinkAdd;
use Drupal\Tests\actionlink_dropdown\Kernel\Concerns\OverridesRequestStack;
use Drupal\user\Entity\User;

class LocalActionRendererTest extends EntityKernelTestBase {
    use OverridesRequestStack;

    protected LocalActionRenderer $localActionRenderer;
    protected RouteProviderInterface $routeProvider;
    protected UrlGeneratorInterface $urlGenerator;
    protected RequestStack $requestStack;
    protected RouteMatchInterface $routeMatch;

    protected function setUp(): void {
        parent::setUp();

        $this->requestStack = $this->createRequestStack();

        $this->enableModules(['actionlink_dropdown']);
        $this->setUpCurrentUser(['uid' => 1]);

        $this->localActionRenderer = \Drupal::service('actionlink_dropdown.renderer');
        $this->routeMatch = \Drupal::service('current_route_match');
        $this->routeProvider = \Drupal::service('router.route_provider');
        $this->urlGenerator = \Drupal::service('url_generator');
    }

    public function testRegularLink(): void {
        $pluginDefinition = [
            'id' => 'test_link',
            'title' => Markup::create('Test link'),
            'weight' => null,
            'route_name' => 'user.admin_index',
            'route_parameters' => [],
            'options' => [],
            'appears_on' => ['<front>'],
            'class' => 'Drupal\menu_ui\Plugin\Menu\LocalAction\MenuLinkAdd',
            'provider' => 'test_provider',
        ];

        $redirectDestination = new RedirectDestination($this->requestStack, $this->urlGenerator);
        $localAction = new MenuLinkAdd([], $pluginDefinition['id'], $pluginDefinition, $this->routeProvider, $redirectDestination);

        /** @var User $user */
        $user = User::load(1);

        $renderElement = $this->localActionRenderer->createRenderElement(
            $localAction,
            $this->routeMatch,
            $user,
            $pluginDefinition['title']->__toString()
        );

        $expected = [
            '#theme' => 'menu_local_action',
            '#link' => [
                'title' => 'Test link',
                'url' => Url::fromRoute($localAction->getRouteName(), $localAction->getRouteParameters($this->routeMatch)),
                'localized_options' => [
                    'query' => [
                        'destination' => '/',
                    ],
                ],
            ],
            '#access' => AccessResult::allowed()->addCacheContexts(['user.permissions']),
            '#weight' => null,
        ];

        $this->assertEquals($expected, $renderElement);
    }
}
