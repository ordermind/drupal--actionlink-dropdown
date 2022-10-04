<?php

namespace Drupal\actionlink_dropdown\ValueObject;

class EntityAddConfig
{
    protected string $entityType;

    public function __construct(string $entityType)
    {
        $this->entityType = $entityType;
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }
}
