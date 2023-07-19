<?php

declare(strict_types=1);

namespace Drupal\Tests\actionlink_dropdown\Unit\Factory;

use Drupal\actionlink_dropdown\Collection\LocalActionOptionCollection;
use Drupal\actionlink_dropdown\Enum\LocalActionLinksTypeEnum;
use Drupal\actionlink_dropdown\Factory\CustomOptionsFactory;
use Drupal\actionlink_dropdown\Factory\EntityAddOptionsFactory;
use Drupal\actionlink_dropdown\Factory\OptionsFactory;
use Drupal\actionlink_dropdown\ValueObject\CustomLinksConfig;
use Drupal\actionlink_dropdown\ValueObject\EntityAddConfig;
use Drupal\actionlink_dropdown\ValueObject\LocalActionOption;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Render\Markup;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class OptionsFactoryTest extends UnitTestCase {
  use ProphecyTrait;

  public function testCreateOptionsThrowsExceptionOnMissingLinksKey(): void {
    $customOptionsFactory = $this->prophesize(CustomOptionsFactory::class)->reveal();
    $entityAddOptionsFactory = $this->prophesize(EntityAddOptionsFactory::class)->reveal();
    $account = $this->prophesize(AccountInterface::class)->reveal();
    $translationContext = 'test_context';

    $optionsFactory = new OptionsFactory($customOptionsFactory, $entityAddOptionsFactory);

    $this->expectExceptionObject(new \InvalidArgumentException('The config array must include the key "links"'));
    $optionsFactory->createOptions([], $account, $translationContext);
  }

  public function testCreateOptionsThrowsExceptionOnIllegalLinksKey(): void {
    $customOptionsFactory = $this->prophesize(CustomOptionsFactory::class)->reveal();
    $entityAddOptionsFactory = $this->prophesize(EntityAddOptionsFactory::class)->reveal();
    $account = $this->prophesize(AccountInterface::class)->reveal();
    $translationContext = 'test_context';
    $config = [
      'links' => 'invalid',
    ];

    $optionsFactory = new OptionsFactory($customOptionsFactory, $entityAddOptionsFactory);

    $this->expectExceptionObject(new \InvalidArgumentException('The value "invalid" is not supported for the "links" key'));
    $optionsFactory->createOptions($config, $account, $translationContext);
  }

  public function testCreateOptionsCanUseCustomLinksFactory(): void {
    $expectedOptions = new LocalActionOptionCollection([
      new LocalActionOption(
        Markup::create('Go to Test link'),
        Markup::create('Go to Test link'), 
        AccessResult::allowed(), 
        'user.admin_index', 
        ['key' => 'value']
      ),
    ]);

    $account = $this->prophesize(AccountInterface::class)->reveal();
    $translationContext = 'test_context';
    $config = [
      'links' => LocalActionLinksTypeEnum::CUSTOM,
      'custom_links' => [
        [
          'title' => 'Test link',
          'route_name' => 'user.admin_index',
          'route_parameters' => ['key' => 'value'],
        ],
      ],
      'fallback_title_prefix' => 'Go to',
    ];

    $mockCustomOptionsFactory = $this->prophesize(CustomOptionsFactory::class);
    $mockCustomOptionsFactory->create(CustomLinksConfig::fromArray($config), $account, $translationContext)->willReturn($expectedOptions);
    $customOptionsFactory = $mockCustomOptionsFactory->reveal();
    $entityAddOptionsFactory = $this->prophesize(EntityAddOptionsFactory::class)->reveal();

    $optionsFactory = new OptionsFactory($customOptionsFactory, $entityAddOptionsFactory);

    $options = $optionsFactory->createOptions($config, $account, $translationContext);
    $this->assertEquals($expectedOptions, $options);
  }

  public function testCreateOptionsCanUseEntityAddLinksFactory(): void {
    $expectedOptions = new LocalActionOptionCollection([
      new LocalActionOption(
        Markup::create('Go to Test link'),
        Markup::create('Go to Test link'),
        AccessResult::allowed(), 
        'user.admin_index', 
        ['key' => 'value']
      ),
    ]);

    $account = $this->prophesize(AccountInterface::class)->reveal();
    $translationContext = 'test_context';
    $config = [
      'links' => LocalActionLinksTypeEnum::ENTITY_ADD,
      'entity_type' => 'test',
    ];

    $customOptionsFactory = $this->prophesize(CustomOptionsFactory::class)->reveal();
    $mockEntityAddOptionsFactory = $this->prophesize(EntityAddOptionsFactory::class);
    $mockEntityAddOptionsFactory->create(EntityAddConfig::fromArray($config), $account, $translationContext)->willReturn($expectedOptions);
    $entityAddOptionsFactory = $mockEntityAddOptionsFactory->reveal();

    $optionsFactory = new OptionsFactory($customOptionsFactory, $entityAddOptionsFactory);

    $options = $optionsFactory->createOptions($config, $account, $translationContext);
    $this->assertEquals($expectedOptions, $options);
  }

}
