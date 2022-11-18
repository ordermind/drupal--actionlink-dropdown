<?php

declare(strict_types=1);

namespace Drupal\actionlink_dropdown\Concerns;

use LogicException;

trait DecoratesObject {
    protected object $decoratedObject;

    public function __call(string $method, array $args) {
        if (is_callable([$this->decoratedObject, $method])) {
            return call_user_func_array([$this->decoratedObject, $method], $args);
        }
        throw new LogicException(
            'Undefined method - ' . get_class($this->decoratedObject) . '::' . $method
        );
    }

    public function __get($property) {
        if (property_exists($this->decoratedObject, $property)) {
            return $this->decoratedObject->$property;
        }

        return null;
    }

    public function __set($property, $value) {
        $this->decoratedObject->$property = $value;

        return $this;
    }

    public function getDecoratedObject(): object {
        return $this->decoratedObject;
    }
}
