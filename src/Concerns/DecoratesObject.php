<?php

declare(strict_types=1);

namespace Drupal\actionlink_dropdown\Concerns;

use LogicException;

trait DecoratesObject {
    protected object $object;

    public function __call(string $method, array $args) {
        if (is_callable($this->object, $method)) {
            return call_user_func_array([$this->object, $method], $args);
        }
        throw new LogicException(
            'Undefined method - ' . get_class($this->object) . '::' . $method
        );
    }

    public function __get($property) {
        if (property_exists($this->object, $property)) {
            return $this->object->$property;
        }

        return null;
    }

    public function __set($property, $value) {
        $this->object->$property = $value;

        return $this;
    }
}
