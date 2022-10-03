<?php

namespace Drupal\actionlink_dropdown\Collection;

use Drupal\actionlink_dropdown\ValueObject\LocalActionOption;
use Gamez\Illuminate\Support\TypedCollection;

class LocalActionOptionCollection extends TypedCollection
{
    protected static $allowedTypes = [LocalActionOption::class];
}
