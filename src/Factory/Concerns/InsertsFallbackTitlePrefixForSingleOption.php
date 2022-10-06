<?php

namespace Drupal\actionlink_dropdown\Factory\Concerns;

use Drupal\actionlink_dropdown\Collection\LocalActionOptionCollection;
use Drupal\actionlink_dropdown\ValueObject\LocalActionOption;
use Drupal\Core\StringTranslation\StringTranslationTrait;

trait InsertsFallbackTitlePrefixForSingleOption
{
    use StringTranslationTrait;

    /**
     * If there is only one option, translate the fallback title prefix and insert it into the option label.
     */
    protected function insertFallbackTitlePrefixForSingleOption(
        LocalActionOptionCollection $options,
        string $fallbackTitlePrefix,
        string $translationContext
    ): LocalActionOptionCollection {
        if ($options->count() !== 1) {
            return $options;
        }

        /** @var LocalActionOption $firstOption */
        $firstOption = $options->firstOrFail();
        return new LocalActionOptionCollection([
            new LocalActionOption(
                $this->t(
                    "${fallbackTitlePrefix} @option",
                    ['@option' => $firstOption->getTitle()],
                    ['context' => $translationContext]
                ),
                $firstOption->getRouteName(),
                $firstOption->getRouteParameters()
            )
        ]);
    }
}
