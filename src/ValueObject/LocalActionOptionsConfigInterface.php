<?php

declare(strict_types=1);

namespace Drupal\actionlink_dropdown\ValueObject;

interface LocalActionOptionsConfigInterface {

  /**
   * Returns the fallback title prefix, which is used if there is only one option.
   */
  public function getFallbackTitlePrefix(): ?string;
}
