<?php

declare(strict_types=1);

namespace Drupal\Tests\actionlink_dropdown\Unit\Factory;

use Drupal\actionlink_dropdown\Collection\CustomLinkCollection;
use Drupal\actionlink_dropdown\Collection\LocalActionOptionCollection;
use Drupal\actionlink_dropdown\Factory\CustomOptionsFactory;
use Drupal\actionlink_dropdown\ValueObject\CustomLink;
use Drupal\actionlink_dropdown\ValueObject\CustomLinksConfig;
use Drupal\actionlink_dropdown\ValueObject\LocalActionOption;
use Drupal\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Access\AccessResultNeutral;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class CustomOptionsFactoryTest extends UnitTestCase {
  use ProphecyTrait;

  protected function setUp(): void {
    parent::setUp();

    $mockCacheContextManager = $this->prophesize(CacheContextsManager::class);
    $mockCacheContextManager->assertValidTokens(Argument::cetera())->willReturn(TRUE);
    $cacheContextManager = $mockCacheContextManager->reveal();

    $mockTranslationService = $this->prophesize(TranslationInterface::class);
    $translationService = $mockTranslationService->reveal();

    $mockContainer = $this->prophesize(ContainerInterface::class);
    $mockContainer->get('cache_contexts_manager')->willReturn($cacheContextManager);
    $mockContainer->get('string_translation')->willReturn($translationService);
    $container = $mockContainer->reveal();

    \Drupal::setContainer($container);
  }

  /**
   * @dataProvider singleOptionProvider
   */
  public function testCreateSingleOption(string $expectedTitleTranslationString, array $expectedTitleTranslationArguments, ?string $fallbackTitlePrefix): void {
    $mockAccount = $this->prophesize(AccountInterface::class);
    $account = $mockAccount->reveal();

    if (!empty($expectedTitleTranslationArguments['@option'])) {
      $expectedTitleTranslationArguments['@option'] = new TranslatableMarkup(
        $expectedTitleTranslationArguments['@option'],
        [],
        ['context' => 'test_context'],
        \Drupal::service('string_translation')
      );
    }

    $translatedTitle = new TranslatableMarkup(
      $expectedTitleTranslationString,
      $expectedTitleTranslationArguments,
      ['context' => 'test_context'],
      \Drupal::service('string_translation')
    );

    $expectedOptions = new LocalActionOptionCollection([
      new LocalActionOption(
        $translatedTitle,
        new TranslatableMarkup(
          (string) $fallbackTitlePrefix . ' @option',
          ['@option' => $translatedTitle],
          ['context' => 'test_context'],
          \Drupal::service('string_translation')
        ),
        AccessResult::forbidden()->addCacheContexts(['user.permissions']),
        'route_1',
        ['key-1' => 'value-1'],
      ),
    ]);

    $config = new CustomLinksConfig(
      new CustomLinkCollection([
        new CustomLink('Option 1', 'route_1', ['key-1' => 'value-1']),
      ]),
      $fallbackTitlePrefix
    );
    $customLink = $config->getLinks()->get(0);

    $mockAccessManager = $this->prophesize(AccessManagerInterface::class);
    $mockAccessManager->checkNamedRoute(
      $customLink->getRouteName(),
      $customLink->getRouteParameters(),
      $account,
      TRUE
    )->willReturn(AccessResult::forbidden()->addCacheContexts(['user.permissions']));
    $accessManager = $mockAccessManager->reveal();

    $factory = new CustomOptionsFactory($accessManager);
    $options = $factory->create($config, $account, 'test_context');

    $this->assertEquals($expectedOptions, $options);
  }

  public function singleOptionProvider(): array {
    return [
      ['Option 1', [], NULL],
      ['Option 1', [], ''],
      ['Option 1', [], 'Test Prefix'],
    ];
  }

  /**
   * @dataProvider fallbackTitlePrefixProvider
   */
  public function testCreateMultipleOptions(?string $fallbackTitlePrefix): void {
    $mockAccount = $this->prophesize(AccountInterface::class);
    $account = $mockAccount->reveal();

    $translatedTitles = [
      new TranslatableMarkup('Option 1', [], ['context' => 'test_context'], \Drupal::service('string_translation')),
      new TranslatableMarkup('Option 2', [], ['context' => 'test_context'], \Drupal::service('string_translation')),
      new TranslatableMarkup('Option 3', [], ['context' => 'test_context'], \Drupal::service('string_translation')),
    ];

    $expectedOptions = new LocalActionOptionCollection([
      new LocalActionOption(
        $translatedTitles[0],
        new TranslatableMarkup(
          (string) $fallbackTitlePrefix . ' @option', 
          ['@option' => $translatedTitles[0]], 
          ['context' => 'test_context'], 
          \Drupal::service('string_translation')
        ),
        AccessResult::forbidden()->addCacheContexts(['user.permissions']),
        'route_1',
        ['key-1' => 'value-1'],
      ),
      new LocalActionOption(
        $translatedTitles[1],
        new TranslatableMarkup(
          (string) $fallbackTitlePrefix . ' @option', 
          ['@option' => $translatedTitles[1]], 
          ['context' => 'test_context'], 
          \Drupal::service('string_translation')
        ),
        AccessResult::neutral()->addCacheContexts(['user.permissions']),
        'route_2',
        ['key-2' => 'value-2'],
      ),
      new LocalActionOption(
        $translatedTitles[2],
        new TranslatableMarkup(
          (string) $fallbackTitlePrefix . ' @option', 
          ['@option' => $translatedTitles[2]], 
          ['context' => 'test_context'], 
          \Drupal::service('string_translation')
        ),
        AccessResult::allowed()->addCacheContexts(['user.permissions']),
        'route_3',
        ['key-3' => 'value-3'],
      ),
    ]);

    $config = new CustomLinksConfig(
      new CustomLinkCollection([
        new CustomLink('Option 1', 'route_1', ['key-1' => 'value-1']),
        new CustomLink('Option 2', 'route_2', ['key-2' => 'value-2']),
        new CustomLink('Option 3', 'route_3', ['key-3' => 'value-3']),
      ]),
      $fallbackTitlePrefix
    );

    $accessResults = array_map(
          fn (string $accessResultClass) => (new $accessResultClass())->addCacheContexts(['user.permissions']),
          [AccessResultForbidden::class, AccessResultNeutral::class, AccessResultAllowed::class]
      );

    $mockAccessManager = $this->prophesize(AccessManagerInterface::class);
    $config->getLinks()->each(
      function (CustomLink $customLink, int $index) use ($expectedOptions, $mockAccessManager, $accessResults, $account) {
        $expectedOptions->get($index)->getAccessResult()->addCacheContexts(['user.permissions']);

        $mockAccessManager->checkNamedRoute(
            $customLink->getRouteName(),
            $customLink->getRouteParameters(),
            $account,
            TRUE
        )->willReturn($accessResults[$index]);
      }
    );
    $accessManager = $mockAccessManager->reveal();

    $factory = new CustomOptionsFactory($accessManager);
    $options = $factory->create($config, $account, 'test_context');

    $this->assertEquals($expectedOptions, $options);
  }

  public function fallbackTitlePrefixProvider(): array {
    return [
      [NULL],
      [''],
      ['Test Prefix'],
    ];
  }

}
