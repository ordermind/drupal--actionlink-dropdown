<?php

declare(strict_types=1);

namespace Drupal\Tests\actionlink_dropdown\Kernel;

use Drupal\actionlink_dropdown\Render\LocalActionRenderer;
use Drupal\Core\Http\RequestStack;
use Drupal\Core\Render\Markup;
use Drupal\Core\Routing\RedirectDestination;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\menu_ui\Plugin\Menu\LocalAction\MenuLinkAdd;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

class LocalActionRendererTest extends EntityKernelTestBase {
    protected LocalActionRenderer $localActionRenderer;
    protected RouteProviderInterface $routeProvider;
    protected UrlGeneratorInterface $urlGenerator;

    protected function setUp(): void {
        parent::setUp();

        $this->enableModules(['actionlink_dropdown']);
        $this->setUpCurrentUser(['uid' => 1]);

        $this->localActionRenderer = \Drupal::service('actionlink_dropdown.renderer');
        $this->routeProvider = \Drupal::service('router.route_provider');
        $this->urlGenerator = \Drupal::service('url_generator');
    }

    public function testRegularLink(): void {
        $pluginDefinition = [
            'id' => 'test_link',
            'title' => Markup::create('Test link'),
            'weight' => null,
            'route_name' => '<front>',
            'route_parameters' => [],
            'options' => [],
            'appears_on' => ['user.admin_index'],
            'class' => 'Drupal\menu_ui\Plugin\Menu\LocalAction\MenuLinkAdd',
            'provider' => 'test_provider',
        ];
        $request = Request::create('/admin/config/people');
        $requestStack = new RequestStack();
        $requestStack->push($request);
        $redirectDestination = new RedirectDestination($requestStack, $this->urlGenerator);

        $localAction = new MenuLinkAdd([], $pluginDefinition['id'], $pluginDefinition, $this->routeProvider, $redirectDestination);
        $routeMatch = new RouteMatch('user.admin_index', new Route('/admin/config/people'));
        /** @var User $user */
        $user = User::load(1);

        dump($this->localActionRenderer->createRenderElement($localAction, $routeMatch, $user, $pluginDefinition['title']->__toString()));
    }
}
