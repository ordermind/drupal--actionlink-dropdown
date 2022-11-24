<?php

declare(strict_types=1);

namespace Drupal\Tests\actionlink_dropdown\Kernel\Factory;

use Drupal\actionlink_dropdown\Factory\CacheableLocalActionLinksFactory;
use Drupal\actionlink_dropdown\Render\LocalActionRenderer;
use Drupal\actionlink_dropdown\ValueObject\LocalizedLocalActionDecorator;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Http\RequestStack;
use Drupal\Core\Render\Markup;
use Drupal\Core\Routing\RedirectDestination;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Url;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\menu_ui\Plugin\Menu\LocalAction\MenuLinkAdd;
use Drupal\Tests\actionlink_dropdown\Kernel\Traits\OverridesRequestStack;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\user\Entity\User;

class CacheableLocalActionLinksFactoryTest extends EntityKernelTestBase {
  use OverridesRequestStack;
  use ContentTypeCreationTrait;

  protected CacheableLocalActionLinksFactory $factory;
  protected LocalActionRenderer $localActionRenderer;
  protected RouteProviderInterface $routeProvider;
  protected UrlGeneratorInterface $urlGenerator;
  protected RequestStack $requestStack;
  protected RouteMatchInterface $routeMatch;

  protected function setUp(): void {
    parent::setUp();

    $this->requestStack = $this->createRequestStack();

    $this->enableModules(['node', 'actionlink_dropdown']);
    $this->installConfig(['node']);
    $this->setUpCurrentUser(['uid' => 1]);

    $this->factory = \Drupal::service('actionlink_dropdown.local_action_links_factory');
    $this->localActionRenderer = \Drupal::service('actionlink_dropdown.renderer');
    $this->routeMatch = \Drupal::service('current_route_match');
    $this->routeProvider = \Drupal::service('router.route_provider');
    $this->urlGenerator = \Drupal::service('url_generator');
  }

  public function testRegularLink(): void {
    $pluginDefinition = [
      'id' => 'test_link',
      'title' => Markup::create('Test link'),
      'weight' => NULL,
      'route_name' => 'user.admin_index',
      'route_parameters' => [],
      'options' => [],
      'appears_on' => ['<front>'],
      'class' => 'Drupal\menu_ui\Plugin\Menu\LocalAction\MenuLinkAdd',
      'provider' => 'test_provider',
    ];

    $redirectDestination = new RedirectDestination($this->requestStack, $this->urlGenerator);
    $localAction = new MenuLinkAdd([], $pluginDefinition['id'], $pluginDefinition, $this->routeProvider, $redirectDestination);

    /** @var \Drupal\user\Entity\User $user */
    $user = User::load(1);

    $renderElement = $this->factory->createFromLocalizedLocalActions(
          $this->routeMatch,
          $user,
          new LocalizedLocalActionDecorator($localAction, $pluginDefinition['title']->__toString()),
      );

    $expected = [
      'test_link' => [
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
        '#weight' => NULL,
      ],
      '#cache' => [
        'contexts' => [
          'route',
          'user.permissions',
        ],
        'tags' => [],
        'max-age' => 0,
      ],
    ];

    $this->assertEquals($expected, $renderElement);
  }

  public function testSingleCustomLink(): void {
    $pluginDefinition = [
      'id' => 'entity_add_links',
      'title' => Markup::create('Go to link'),
      'weight' => 5,
      'route_name' => '<front>',
      'route_parameters' => [],
      'options' => [
        'widget' => 'details',
        'links' => 'custom',
        'custom_links' => [
                  [
                    'title' => 'Test link',
                    'route_name' => 'user.admin_index',
                    'route_parameters' => [],
                  ],
        ],
        'fallback_title_prefix' => 'Go to',
      ],
      'appears_on' => ['<front>'],
      'class' => 'Drupal\menu_ui\Plugin\Menu\LocalAction\MenuLinkAdd',
      'provider' => 'test_provider',
    ];

    $redirectDestination = new RedirectDestination($this->requestStack, $this->urlGenerator);
    $localAction = new MenuLinkAdd([], $pluginDefinition['id'], $pluginDefinition, $this->routeProvider, $redirectDestination);

    /** @var \Drupal\user\Entity\User $user */
    $user = User::load(1);

    $renderElement = $this->factory->createFromLocalizedLocalActions(
          $this->routeMatch,
          $user,
          new LocalizedLocalActionDecorator($localAction, $pluginDefinition['title']->__toString()),
      );

    $expected = [
      'entity_add_links' => [
        '#theme' => 'menu_local_action',
        '#link' => [
          'title' => 'Go to Test link',
          'url' => Url::fromRoute(
                      $pluginDefinition['options']['custom_links'][0]['route_name'],
                      $pluginDefinition['options']['custom_links'][0]['route_parameters'],
          ),
          'localized_options' => array_merge(
                      $pluginDefinition['options'],
                      [
                        'query' => [
                          'destination' => '/',
                        ],
                      ]
          ),
        ],
        '#access' => AccessResult::allowed()->addCacheContexts(['user.permissions']),
        '#weight' => 5,
      ],
      '#cache' => [
        'contexts' => [
          'route',
          'user.permissions',
        ],
        'tags' => [],
        'max-age' => 0,
      ],
    ];

    $this->assertEquals($expected, $renderElement);
  }

  public function testMultipleEntityAddLinks(): void {
    $this->createContentType(['type' => 'bundle_1', 'name' => 'Bundle 1']);
    $this->createContentType(['type' => 'bundle_2', 'name' => 'Bundle 2']);

    $pluginDefinition = [
      'id' => 'entity_add_links',
      'title' => Markup::create('Add node'),
      'weight' => 5,
      'route_name' => '<front>',
      'route_parameters' => [],
      'options' => [
        'widget' => 'details',
        'links' => 'entity_add',
        'entity_type' => 'node',
      ],
      'appears_on' => ['<front>'],
      'class' => 'Drupal\menu_ui\Plugin\Menu\LocalAction\MenuLinkAdd',
      'provider' => 'test_provider',
    ];

    $redirectDestination = new RedirectDestination($this->requestStack, $this->urlGenerator);
    $localAction = new MenuLinkAdd([], $pluginDefinition['id'], $pluginDefinition, $this->routeProvider, $redirectDestination);

    /** @var \Drupal\user\Entity\User $user */
    $user = User::load(1);

    $renderElement = $this->factory->createFromLocalizedLocalActions(
          $this->routeMatch,
          $user,
          new LocalizedLocalActionDecorator($localAction, $pluginDefinition['title']->__toString()),
      );

    $expected = [
      'entity_add_links' => [
        '#theme' => 'actionlink_dropdown_details',
        '#dropdown' => [
          'title' => 'Add node',
          'options' => [
                      [
                        'title' => Markup::create('Bundle 1'),
                        'route_name' => 'node.add',
                        'route_parameters' => [
                          'node_type' => 'bundle_1',
                        ],
                        'access' => AccessResult::allowed()->addCacheContexts(['user.permissions']),
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
      ],
      '#cache' => [
        'contexts' => [
          'route',
          'user.permissions',
        ],
        'tags' => [],
        'max-age' => 0,
      ],
    ];

    $this->assertEquals($expected, $renderElement);
  }

}
