<?php

namespace Drupal\actionlink_dropdown\ValueObject;

use Drupal\Component\Render\MarkupInterface;

class LocalActionOption
{
    protected MarkupInterface $title;
    protected string $routeName;
    protected array $routeParameters;

    public function __construct(MarkupInterface $title, string $routeName, array $routeParameters = [])
    {
        $this->title = $title;
        $this->routeName = $routeName;
        $this->routeParameters = $routeParameters;
    }

    public function getTitle(): MarkupInterface
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

    public function toArray(): array
    {
        return [
            'title' => $this->getTitle(),
            'route_name' => $this->getRouteName(),
            'route_parameters' => $this->getRouteParameters(),
        ];
    }
}
