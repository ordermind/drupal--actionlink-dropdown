<?php

declare(strict_types=1);

namespace Drupal\Tests\actionlink_dropdown\Unit\ValueObject;

use Drupal\actionlink_dropdown\ValueObject\EntityAddConfig;
use Drupal\Tests\UnitTestCase;

class EntityAddConfigTest extends UnitTestCase {

  public function testFromArrayThrowsExceptionOnMissingEntityTypeKey(): void {
    $config = [];

    $this->expectExceptionObject(new \InvalidArgumentException('The config array must contain a value for the key "entity_type"'));
    EntityAddConfig::fromArray($config);
  }

  /**
   * @dataProvider fromArrayThrowsExceptionOnEmptyEntityTypeValueProvider
   */
  public function testFromArrayThrowsExceptionOnEmptyEntityTypeValue($entity_type): void {
    $config = ['entity_type' => $entity_type];

    $this->expectExceptionObject(new \InvalidArgumentException('The config array must contain a value for the key "entity_type"'));
    EntityAddConfig::fromArray($config);
  }

  public static function fromArrayThrowsExceptionOnEmptyEntityTypeValueProvider(): array {
    return [
      [NULL],
      [''],
    ];
  }

  public function testFromArrayThrowsExceptionOnIllegalEntityTypeType(): void {
    $config = ['entity_type' => 1];

    $this->expectExceptionObject(new \InvalidArgumentException('The value for the key "entity_type" must be a string'));
    EntityAddConfig::fromArray($config);
  }

  public function testFromArrayThrowsExceptionOnIllegalFallbackTitlePrefixType(): void {
    $values = [
      'entity_type' => 'test_entity_type',
      'fallback_title_prefix' => 1,
    ];

    $this->expectExceptionObject(new \InvalidArgumentException('The value for the key "fallback_title_prefix" must be a string'));
    EntityAddConfig::fromArray($values);
  }

  /**
   * @dataProvider fromArrayCreatesValidObjectProvider
   */
  public function testFromArrayCreatesValidObject(string $expectedEntityType, ?string $expectedFallbackTitlePrefix, array $input): void {
    $config = EntityAddConfig::fromArray($input);

    $this->assertEquals($expectedEntityType, $config->getEntityTypeId());
    $this->assertEquals($expectedFallbackTitlePrefix, $config->getFallbackTitlePrefix());
  }

  public static function fromArrayCreatesValidObjectProvider(): array {
    $entityType = 'test_entity_type';

    return [
      [
        $entityType,
        'Add',
        ['entity_type' => $entityType],
      ],
      [
        $entityType,
        'Add',
        ['entity_type' => $entityType, 'fallback_title_prefix' => NULL],
      ],
      [
        $entityType,
        'Add',
        ['entity_type' => $entityType, 'fallback_title_prefix' => ''],
      ],
      [
        $entityType,
        'Test Prefix',
        ['entity_type' => $entityType, 'fallback_title_prefix' => 'Test Prefix'],
      ],
    ];
  }

}
