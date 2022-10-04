<?php

namespace Drupal\actionlink_dropdown\Factory;

use Drupal\actionlink_dropdown\Collection\LocalActionOptionCollection;
use Drupal\actionlink_dropdown\ValueObject\EntityAddConfig;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

class EntityAddOptionsFactory
{
    use StringTranslationTrait;

    protected AccessManagerInterface $accessManager;

    public function __construct(AccessManagerInterface $accessManager)
    {
        $this->accessManager = $accessManager;
    }

    public function create(EntityAddConfig $config, AccountInterface $account, string $translationContext): LocalActionOptionCollection
    {
        // TODO

        return new LocalActionOptionCollection();
    }
}
