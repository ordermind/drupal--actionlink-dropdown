<?php

declare(strict_types=1);

namespace Drupal\Tests\actionlink_dropdown\Unit\ValueObject;

use Drupal\actionlink_dropdown\Collection\CustomLinkCollection;
use Drupal\actionlink_dropdown\ValueObject\CustomLink;
use Drupal\actionlink_dropdown\ValueObject\CustomLinksConfig;
use Drupal\Tests\UnitTestCase;

class CustomLinksConfigTest extends UnitTestCase {

  public function testFromArrayThrowsExceptionOnMissingCustomLinksKey(): void {
    $config = [];

    $this->expectExceptionObject(new \InvalidArgumentException('The config array must contain a value for the key "custom_links"'));
    CustomLinksConfig::fromArray($config);
  }

  public function testFromArrayThrowsExceptionOnEmptyCustomLinksValue(): void {
    $config = ['custom_links' => NULL];

    $this->expectExceptionObject(new \InvalidArgumentException('The config array must contain a value for the key "custom_links"'));
    CustomLinksConfig::fromArray($config);
  }

  public function testFromArrayThrowsExceptionOnIllegalCustomLinksType(): void {
    $config = ['custom_links' => 1];

    $this->expectExceptionObject(new \InvalidArgumentException('The value for the key "custom_links" must be an array'));
    CustomLinksConfig::fromArray($config);
  }

  public function testFromArrayThrowsExceptionOnIllegalFallbackTitlePrefixType(): void {
    $config = [
      'custom_links' => [
        [
          'title' => 'Test Title',
          'route_name' => 'test_route',
        ],
      ],
      'fallback_title_prefix' => 1,
    ];

    $this->expectExceptionObject(new \InvalidArgumentException('The value for the key "fallback_title_prefix" must be a string'));
    CustomLinksConfig::fromArray($config);
  }

  /**
   * @dataProvider fromArrayCreatesValidObjectProvider
   */
  public function testFromArrayCreatesValidObject(CustomLinkCollection $expectedLinks, ?string $expectedFallbackTitlePrefix, array $input): void {
    $config = CustomLinksConfig::fromArray($input);

    $this->assertEquals($expectedLinks, $config->getLinks());
    $this->assertEquals($expectedFallbackTitlePrefix, $config->getFallbackTitlePrefix());
  }

  public function fromArrayCreatesValidObjectProvider(): array {
    $expectedLinks = new CustomLinkCollection([
      new CustomLink('Test Link', 'test_route'),
    ]);
    $linksInput = [
      ['title' => 'Test Link', 'route_name' => 'test_route'],
    ];

    return [
      [
        $expectedLinks,
        NULL,
        ['custom_links' => $linksInput],
      ],
      [
        $expectedLinks,
        NULL,
        ['custom_links' => $linksInput, 'fallback_title_prefix' => NULL],
      ],
      [
        $expectedLinks,
        NULL,
        ['custom_links' => $linksInput, 'fallback_title_prefix' => ''],
      ],
      [
        $expectedLinks,
        'Test Prefix',
        ['custom_links' => $linksInput, 'fallback_title_prefix' => 'Test Prefix'],
      ],
    ];
  }

}
