<?php

declare(strict_types=1);

namespace Drupal\Tests\actionlink_dropdown\Unit\ValueObject;

use Drupal\actionlink_dropdown\ValueObject\CustomLink;
use Drupal\Tests\UnitTestCase;

class CustomLinkTest extends UnitTestCase {

  public function testFromArrayThrowsExceptionOnMissingTitleKey(): void {
    $values = [];

    $this->expectExceptionObject(new \InvalidArgumentException('The values array must contain a value for the key "title"'));
    CustomLink::fromArray($values);
  }

  /**
   * @dataProvider fromArrayThrowsExceptionOnEmptyTitleValueProvider
   */
  public function testFromArrayThrowsExceptionOnEmptyTitleValue($title): void {
    $values = ['title' => $title];

    $this->expectExceptionObject(new \InvalidArgumentException('The values array must contain a value for the key "title"'));
    CustomLink::fromArray($values);
  }

  public function fromArrayThrowsExceptionOnEmptyTitleValueProvider(): array {
    return [
          [NULL],
          [''],
    ];
  }

  public function testFromArrayThrowsExceptionOnIllegalTitleType(): void {
    $values = ['title' => 1];

    $this->expectExceptionObject(new \InvalidArgumentException('The value for the key "title" must be a string'));
    CustomLink::fromArray($values);
  }

  public function testFromArrayThrowsExceptionOnMissingRouteNameKey(): void {
    $values = ['title' => 'test'];

    $this->expectExceptionObject(new \InvalidArgumentException('The values array must contain a value for the key "route_name"'));
    CustomLink::fromArray($values);
  }

  public function testFromArrayThrowsExceptionOnEmptyRouteNameValue(): void {
    $values = ['title' => 'test', 'route_name' => NULL];

    $this->expectExceptionObject(new \InvalidArgumentException('The values array must contain a value for the key "route_name"'));
    CustomLink::fromArray($values);
  }

  public function testFromArrayThrowsExceptionOnIllegalRouteNameType(): void {
    $values = ['title' => 'test', 'route_name' => 1];

    $this->expectExceptionObject(new \InvalidArgumentException('The value for the key "route_name" must be a string'));
    CustomLink::fromArray($values);
  }

  /**
   * @dataProvider fromArrayCreatesValidObjectProvider
   */
  public function testFromArrayCreatesValidObject(string $expectedTitle, string $expectedRouteName, array $expectedRouteParameters, array $input): void {
    $customLink = CustomLink::fromArray($input);

    $this->assertEquals($expectedTitle, $customLink->getTitle());
    $this->assertEquals($expectedRouteName, $customLink->getRouteName());
    $this->assertEquals($expectedRouteParameters, $customLink->getRouteParameters());
  }

  public function fromArrayCreatesValidObjectProvider(): array {
    $title = 'Test Title';
    $routeName = 'test_route';
    return [
          [
            $title,
            $routeName,
              [],
              [
                'title' => $title,
                'route_name' => $routeName,
              ],
          ],
          [
            $title,
            $routeName,
              [],
              [
                'title' => $title,
                'route_name' => $routeName,
                'route_parameters' => NULL,
              ],
          ],
          [
            $title,
            $routeName,
              ['key' => 'value'],
              [
                'title' => $title,
                'route_name' => $routeName,
                'route_parameters' => ['key' => 'value'],
              ],
          ],
    ];
  }

}
