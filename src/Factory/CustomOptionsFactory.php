<?php

declare(strict_types=1);

namespace Drupal\actionlink_dropdown\Factory;

use Drupal\actionlink_dropdown\Collection\LocalActionOptionCollection;
use Drupal\actionlink_dropdown\ValueObject\CustomLink;
use Drupal\actionlink_dropdown\ValueObject\CustomLinksConfig;
use Drupal\actionlink_dropdown\ValueObject\LocalActionOption;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

class CustomOptionsFactory {
  use StringTranslationTrait;

  protected AccessManagerInterface $accessManager;

  public function __construct(AccessManagerInterface $accessManager) {
    $this->accessManager = $accessManager;
  }

  public function create(CustomLinksConfig $config, AccountInterface $account, string $translationContext): LocalActionOptionCollection {
    return new LocalActionOptionCollection(
      $config
        ->getLinks()
        ->untype()
        ->map(
          function (CustomLink $option) use ($config, $account, $translationContext) {
            $translatedTitle = $this->t($option->getTitle(), [], ['context' => $translationContext]);

            return new LocalActionOption(
              $translatedTitle,
              $this->t($config->getFallbackTitlePrefix() . ' @option',
                ['@option' => $translatedTitle],
                ['context' => $translationContext]
              ),
              $this->accessManager->checkNamedRoute(
                $option->getRouteName(),
                $option->getRouteParameters(),
                $account,
                TRUE
              ),
              $option->getRouteName(),
              $option->getRouteParameters()
            );
          }
        )
        ->toArray()
    );
  }

}
