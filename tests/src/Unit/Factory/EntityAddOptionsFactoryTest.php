<?php

declare(strict_types=1);

namespace Drupal\Tests\actionlink_dropdown\Unit\Factory;

use Drupal\actionlink_dropdown\Collection\LocalActionOptionCollection;
use Drupal\actionlink_dropdown\Factory\EntityAddOptionsFactory;
use Drupal\actionlink_dropdown\ValueObject\EntityAddConfig;
use Drupal\actionlink_dropdown\ValueObject\LocalActionOption;
use Drupal\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class EntityAddOptionsFactoryTest extends UnitTestCase {
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
   * @dataProvider provideEntityTypeIds
   */
  public function testCreateThrowsExceptionIfTheEntityTypeHasNoBundle(string $entityTypeId): void {
    $mockEntityTypeDefinition = $this->prophesize(EntityTypeInterface::class);
    $mockEntityTypeDefinition->getBundleEntityType()->willReturn(NULL);
    $entityTypeDefinition = $mockEntityTypeDefinition->reveal();

    $mockEntityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $mockEntityTypeManager->getDefinition($entityTypeId)->willReturn($entityTypeDefinition);
    $entityTypeManager = $mockEntityTypeManager->reveal();

    $mockBundleInfo = $this->prophesize(EntityTypeBundleInfoInterface::class);
    $bundleInfo = $mockBundleInfo->reveal();

    $mockAccount = $this->prophesize(AccountInterface::class);
    $account = $mockAccount->reveal();

    $mockAccessManager = $this->prophesize(AccessManagerInterface::class);
    $accessManager = $mockAccessManager->reveal();

    $factory = new EntityAddOptionsFactory($entityTypeManager, $bundleInfo, $accessManager);

    $this->expectExceptionObject(new \LogicException("The entity type \"${entityTypeId}\" does not support bundles. Entity types without bundles are not supported for entity add links."));
    $factory->create(new EntityAddConfig($entityTypeId), $account, 'test_context');
  }

  /**
   * @dataProvider provideEntityTypeIds
   */
  public function testCreateReturnsEmptyCollectionIfThereAreNoBundles(string $entityTypeId): void {
    $mockEntityTypeDefinition = $this->prophesize(EntityTypeInterface::class);
    $mockEntityTypeDefinition->getBundleEntityType()->willReturn('test_bundle_entity_type_id');
    $entityTypeDefinition = $mockEntityTypeDefinition->reveal();

    $mockEntityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $mockEntityTypeManager->getDefinition($entityTypeId)->willReturn($entityTypeDefinition);
    $entityTypeManager = $mockEntityTypeManager->reveal();

    $mockBundleInfo = $this->prophesize(EntityTypeBundleInfoInterface::class);
    $mockBundleInfo->getBundleInfo($entityTypeId)->willReturn([]);
    $bundleInfo = $mockBundleInfo->reveal();

    $mockAccount = $this->prophesize(AccountInterface::class);
    $account = $mockAccount->reveal();

    $mockAccessManager = $this->prophesize(AccessManagerInterface::class);
    $accessManager = $mockAccessManager->reveal();

    $factory = new EntityAddOptionsFactory($entityTypeManager, $bundleInfo, $accessManager);

    $options = $factory->create(new EntityAddConfig($entityTypeId), $account, 'test_context');
    $this->assertSame(0, $options->count());
  }

  public function provideEntityTypeIds(): array {
    return [
          ['node'],
          ['test_entity'],
    ];
  }

  /**
   * @dataProvider singleOptionProvider
   */
  public function testCreateSingleOption(
        string $expectedTitleTranslationString,
        array $expectedTitleTranslationArguments,
        bool $expectTranslationMarkup,
        ?string $fallbackTitlePrefix,
        string $expectedRouteName,
        array $expectedRouteParameters,
        string $entityTypeId,
        ?string $bundleEntityTypeId,
        array $entityBundles
    ): void {
    $mockEntityTypeDefinition = $this->prophesize(EntityTypeInterface::class);
    $mockEntityTypeDefinition->getBundleEntityType()->willReturn($bundleEntityTypeId);
    $entityTypeDefinition = $mockEntityTypeDefinition->reveal();

    $mockEntityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $mockEntityTypeManager->getDefinition($entityTypeId)->willReturn($entityTypeDefinition);
    $entityTypeManager = $mockEntityTypeManager->reveal();

    $mockBundleInfo = $this->prophesize(EntityTypeBundleInfoInterface::class);
    $mockBundleInfo->getBundleInfo($entityTypeId)->willReturn($entityBundles);
    $bundleInfo = $mockBundleInfo->reveal();

    $mockAccount = $this->prophesize(AccountInterface::class);
    $account = $mockAccount->reveal();

    $expectedTitle = Markup::create($expectedTitleTranslationString);
    if ($expectTranslationMarkup) {
      $expectedTitle = new TranslatableMarkup(
            $expectedTitleTranslationString,
            $expectedTitleTranslationArguments,
            ['context' => 'test_context'],
            \Drupal::service('string_translation')
        );
    }

    $expectedOptions = new LocalActionOptionCollection([
      new LocalActionOption(
              $expectedTitle,
              AccessResult::forbidden()->addCacheContexts(['user.permissions']),
              $expectedRouteName,
              $expectedRouteParameters,
      ),
    ]);

    $config = new EntityAddConfig($entityTypeId, $fallbackTitlePrefix);

    $mockAccessManager = $this->prophesize(AccessManagerInterface::class);
    $mockAccessManager->checkNamedRoute(
          $expectedRouteName,
          $expectedRouteParameters,
          $account,
          TRUE
      )->willReturn(AccessResult::forbidden()->addCacheContexts(['user.permissions']));
    $accessManager = $mockAccessManager->reveal();

    $factory = new EntityAddOptionsFactory($entityTypeManager, $bundleInfo, $accessManager);
    $options = $factory->create($config, $account, 'test_context');

    $this->assertEquals($expectedOptions, $options);
  }

  public function singleOptionProvider(): array {
    $bundleEntityTypeId = 'test_bundle_entity_type_id';

    $bundles = [
      'bundle_1' => [
        'label' => 'Bundle 1',
        'translatable' => FALSE,
      ],
    ];

    return [
          // Node.
          ['Bundle 1', [], FALSE, NULL, 'node.add', [$bundleEntityTypeId => 'bundle_1'], 'node', $bundleEntityTypeId, $bundles],
          ['Test Prefix @option', ['@option' => 'Bundle 1'], TRUE, 'Test Prefix', 'node.add', [$bundleEntityTypeId => 'bundle_1'], 'node', $bundleEntityTypeId, $bundles],
          // Custom entity type.
          ['Bundle 1', [], FALSE, NULL, 'entity.test_entity.add_form', [$bundleEntityTypeId => 'bundle_1'], 'test_entity', $bundleEntityTypeId, $bundles],
          ['Test Prefix @option', ['@option' => 'Bundle 1'], TRUE, 'Test Prefix', 'entity.test_entity.add_form', [$bundleEntityTypeId => 'bundle_1'], 'test_entity', $bundleEntityTypeId, $bundles],
    ];
  }

  /**
   * @dataProvider createMultipleOptionsProvider
   */
  public function testCreateMultipleOptions(string $expectedRouteName, string $entityTypeId, ?string $fallbackTitlePrefix): void {
    $bundleEntityTypeId = 'test_bundle_entity_type_id';

    $bundles = [
      'bundle_1' => [
        'label' => 'Bundle 1',
        'translatable' => FALSE,
      ],
      'bundle_2' => [
        'label' => 'Bundle 2',
        'translatable' => FALSE,
      ],
    ];

    $mockEntityTypeDefinition = $this->prophesize(EntityTypeInterface::class);
    $mockEntityTypeDefinition->getBundleEntityType()->willReturn($bundleEntityTypeId);
    $entityTypeDefinition = $mockEntityTypeDefinition->reveal();

    $mockEntityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $mockEntityTypeManager->getDefinition($entityTypeId)->willReturn($entityTypeDefinition);
    $entityTypeManager = $mockEntityTypeManager->reveal();

    $mockBundleInfo = $this->prophesize(EntityTypeBundleInfoInterface::class);
    $mockBundleInfo->getBundleInfo($entityTypeId)->willReturn($bundles);
    $bundleInfo = $mockBundleInfo->reveal();

    $mockAccount = $this->prophesize(AccountInterface::class);
    $account = $mockAccount->reveal();

    $mockAccessManager = $this->prophesize(AccessManagerInterface::class);
    $mockAccessManager->checkNamedRoute(
          $expectedRouteName,
          [$bundleEntityTypeId => 'bundle_1'],
          $account,
          TRUE
      )->willReturn(AccessResult::forbidden()->addCacheContexts(['user.permissions']));
    $mockAccessManager->checkNamedRoute(
          $expectedRouteName,
          [$bundleEntityTypeId => 'bundle_2'],
          $account,
          TRUE
      )->willReturn(AccessResult::allowed()->addCacheContexts(['user.permissions']));
    $accessManager = $mockAccessManager->reveal();

    $expectedOptions = new LocalActionOptionCollection([
      new LocalActionOption(
              Markup::create('Bundle 1'),
              AccessResult::forbidden()->addCacheContexts(['user.permissions']),
              $expectedRouteName,
              [$bundleEntityTypeId => 'bundle_1'],
      ),
      new LocalActionOption(
              Markup::create('Bundle 2'),
              AccessResult::allowed()->addCacheContexts(['user.permissions']),
              $expectedRouteName,
              [$bundleEntityTypeId => 'bundle_2'],
      ),
    ]);

    $config = new EntityAddConfig($entityTypeId, $fallbackTitlePrefix);

    $factory = new EntityAddOptionsFactory($entityTypeManager, $bundleInfo, $accessManager);
    $options = $factory->create($config, $account, 'test_context');

    $this->assertEquals($expectedOptions, $options);
  }

  public function createMultipleOptionsProvider(): array {
    return [
          // Node.
          ['node.add', 'node', NULL],
          ['node.add', 'node', ''],
          ['node.add', 'node', 'Test Prefix'],
          // Custom entity type.
          ['entity.test_entity.add_form', 'test_entity', NULL],
          ['entity.test_entity.add_form', 'test_entity', ''],
          ['entity.test_entity.add_form', 'test_entity', 'Test Prefix'],
    ];
  }

}
