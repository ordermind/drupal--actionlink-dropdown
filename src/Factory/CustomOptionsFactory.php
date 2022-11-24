<?php

declare(strict_types=1);

namespace Drupal\actionlink_dropdown\Factory;

use Drupal\actionlink_dropdown\Collection\LocalActionOptionCollection;
use Drupal\actionlink_dropdown\Factory\Concerns\InsertsFallbackTitlePrefixForSingleOption;
use Drupal\actionlink_dropdown\ValueObject\CustomLink;
use Drupal\actionlink_dropdown\ValueObject\CustomLinksConfig;
use Drupal\actionlink_dropdown\ValueObject\LocalActionOption;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Session\AccountInterface;

class CustomOptionsFactory {
  use InsertsFallbackTitlePrefixForSingleOption;

  protected AccessManagerInterface $accessManager;

  public function __construct(AccessManagerInterface $accessManager) {
    $this->accessManager = $accessManager;
  }

  public function create(CustomLinksConfig $config, AccountInterface $account, string $translationContext): LocalActionOptionCollection {
    $options = new LocalActionOptionCollection(
      $config
        ->getLinks()
        ->untype()
        ->map(
          fn (CustomLink $option) => new LocalActionOption(
            $this->t($option->getTitle(), [], ['context' => $translationContext]),
            $this->accessManager->checkNamedRoute(
              $option->getRouteName(),
              $option->getRouteParameters(),
              $account,
              TRUE
            ),
            $option->getRouteName(),
            $option->getRouteParameters()
          )
        )
        ->toArray()
    );

    return $this->insertFallbackTitlePrefixForSingleOption(
      $options,
      $config->getFallbackTitlePrefix(),
      $translationContext
    );
  }

}
