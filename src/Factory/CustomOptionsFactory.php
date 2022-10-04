<?php

namespace Drupal\actionlink_dropdown\Factory;

use Drupal\actionlink_dropdown\Collection\LocalActionOptionCollection;
use Drupal\actionlink_dropdown\ValueObject\CustomLink;
use Drupal\actionlink_dropdown\ValueObject\CustomLinksConfig;
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

    public function create(CustomLinksConfig $config, AccountInterface $account, string $translationContext): LocalActionOptionCollection
    {
        $options = new LocalActionOptionCollection(
            $config
                ->getLinks()
                ->filter(
                    fn (CustomLink $option) => $this->accessManager->checkNamedRoute(
                        $option->getRouteName(),
                        $option->getRouteParameters(),
                        $account,
                        FALSE
                    )
                )
                ->untype()
                ->map(
                    fn (CustomLink $option) => new LocalActionOption(
                        $this->t($option->getTitle(), [], ['context' => $translationContext]),
                        $option->getRouteName(),
                        $option->getRouteParameters()
                    )
                )
                ->toArray()
        );

        if ($options->count() === 1) {
            /** @var LocalActionOption $firstOption */
            $firstOption = $options->firstOrFail();
            return new LocalActionOptionCollection([
                new LocalActionOption(
                    $this->t($config->getFallbackTitlePrefix() . ' @option', ['@option' => $firstOption->getTitle()], ['context' => $translationContext]),
                    $firstOption->getRouteName(),
                    $firstOption->getRouteParameters()
                )
            ]);
        }

        return $options;
    }
}
