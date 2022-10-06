<?php

namespace Drupal\actionlink_dropdown\Collection;

use Drupal\actionlink_dropdown\ValueObject\CustomLink;
use Gamez\Illuminate\Support\TypedCollection;

class CustomLinkCollection extends TypedCollection {
  protected static $allowedTypes = [CustomLink::class];

}
