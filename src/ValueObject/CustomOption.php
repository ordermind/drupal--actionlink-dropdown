<?php

namespace Drupal\actionlink_dropdown\ValueObject;

use Drupal\Component\Render\MarkupInterface;

class CustomOption
{
    protected string $title;
    protected string $routeName;
    protected array $routeParameters;

    public function __construct(string $title, string $routeName, array $routeParameters = [])
    {
        $this->title = $title;
        $this->routeName = $routeName;
        $this->routeParameters = $routeParameters;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getRouteName(): string
    {
        return $this->routeName;
    }

    public function getRouteParameters(): array
    {
        return $this->routeParameters;
    }
}
