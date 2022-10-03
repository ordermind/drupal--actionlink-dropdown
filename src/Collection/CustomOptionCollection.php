<?php

namespace Drupal\actionlink_dropdown\Collection;

use Drupal\actionlink_dropdown\ValueObject\CustomOption;
use Gamez\Illuminate\Support\TypedCollection;

class CustomOptionCollection extends TypedCollection
{
    protected static $allowedTypes = [CustomOption::class];
}
