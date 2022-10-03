<?php

namespace Drupal\actionlink_dropdown\Factory;

use Drupal\actionlink_dropdown\Collection\LocalActionOptionCollection;
use Drupal\actionlink_dropdown\ValueObject\CustomOption;
use Drupal\actionlink_dropdown\ValueObject\CustomOptionsConfig;
use Drupal\actionlink_dropdown\ValueObject\LocalActionOption;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

class CustomOptionsFactory
{
    use StringTranslationTrait;

    protected AccessManagerInterface $accessManager;

    public function __construct(AccessManagerInterface $accessManager)
    {
        $this->accessManager = $accessManager;
    }

    public function create(CustomOptionsConfig $config, AccountInterface $account, string $translationContext): LocalActionOptionCollection
    {
        return new LocalActionOptionCollection(
            $config
                ->getOptions()
                ->filter(
                    fn (CustomOption $option) => $this->accessManager->checkNamedRoute(
                        $option->getRouteName(),
                        $option->getRouteParameters(),
                        $account,
                        FALSE
                    )
                )
                ->untype()
                ->map(
                    fn (CustomOption $option) => new LocalActionOption(
                        $this->t($option->getTitle(), [], ['context' => $translationContext]),
                        $option->getRouteName(),
                        $option->getRouteParameters()
                    )
                )
                ->toArray()
        );
    }
}
