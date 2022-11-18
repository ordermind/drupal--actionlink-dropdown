<?php

declare(strict_types=1);

namespace Drupal\actionlink_dropdown\Concerns;

use LogicException;

/**
 * Trait for decorators so that you do not need to manually pass on every method and property.
 *
 * For IDE autocomplete integration, use the following phpdoc directives:
 * @mixin <decorated-class>
 * @method <decorated-class> getDecoratedObject
 * 
 * @TODO: Move this trait to my php-helpers library and add tests.
 */
trait DecoratesObject {
    protected object $decoratedObject;

    public function __call(string $method, array $args) {
        if (!is_callable([$this->decoratedObject, $method])) {
            throw new LogicException(
                'Undefined method - ' . get_class($this->decoratedObject) . '::' . $method
            );
        }

        return call_user_func_array([$this->decoratedObject, $method], $args);
    }

    public function __get(string $property) {
        if (!property_exists($this->decoratedObject, $property)) {
            return null;
        }

        return $this->decoratedObject->$property;
    }

    public function __set(string $property, $value) {
        $this->decoratedObject->$property = $value;

        return $this;
    }

    public function getDecoratedObject(): object {
        return $this->decoratedObject;
    }
}
