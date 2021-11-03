<?php

namespace Drewlabs\Packages\Database;

use Drewlabs\Support\Immutable\ValueObject;
use Illuminate\Container\Container;

class QueryParamsObject extends ValueObject
{
    public function getJsonableAttributes()
    {
        return ["column", "model"];
    }

    /**
     * {@inheritDoc}
     */
    public function copyWith(array $attributes, $set_guarded = false)
    {
        parent::copyWith($attributes, $set_guarded);
        $this->validateAttributes();
        return $this;
    }

    private function validateAttributes()
    {
        if (
            (null === $this->model) ||
            (is_string($this->model) &&
                !class_exists($this->model)) ||
            (is_object($this->model) &&
                !method_exists($this->model, 'getTable'))
        ) {
            throw new \InvalidArgumentException('Make sure to provide a valid Eloquent model or a
            model with getTable method that returns a string to the ["model" => ModelClass]');
        }
    }

    /**
     * Returns string representation of the current object
     *
     * @return string
     */
    public function toString()
    {
        $model = is_string($this->attributes['model']) ? (function_exists('app') ?
            Container::getInstance()->make(
                $this->attributes['model']
            )->getTable() : (new $this->attributes['model'])->getTable()) :
            $this->attributes['model']->getTable();
        return trim(\drewlabs_core_strings_concat(
            '.',
            ...array_values(array_merge(
                [$model],
                isset($this->attributes['column']) ?
                    [$this->attributes['column']] :
                    []
            ))
        ));
    }

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        return $this->toString();
    }
}
