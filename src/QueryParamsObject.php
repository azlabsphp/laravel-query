<?php

declare(strict_types=1);

/*
 * This file is part of the Drewlabs package.
 *
 * (c) Sidoine Azandrew <azandrewdevelopper@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drewlabs\Packages\Database;

use Drewlabs\Packages\Database\Traits\HasIocContainer;
use Drewlabs\Support\Immutable\ValueObject;

class QueryParamsObject extends ValueObject
{
    use HasIocContainer;
    /**
     * {@inheritDoc}
     */
    public function __toString()
    {
        return $this->toString();
    }

    public function getJsonableAttributes()
    {
        return ['column', 'model'];
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

    /**
     * Returns string representation of the current object.
     *
     * @return string
     */
    public function toString()
    {
        $model = \is_string($this->attributes['model']) ?
            $this->createResolver($this->attributes['model'])()->getTable() :
            $this->attributes['model']->getTable();

        return trim(
            drewlabs_core_strings_concat(
                '.',
                ...array_values(
                    array_merge(
                        [$model],
                        isset($this->attributes['column']) ?
                            [$this->attributes['column']] :
                            []
                    )
                )
            )
        );
    }

    private function validateAttributes()
    {
        if (
            (null === $this->model) ||
            (\is_string($this->model) &&
                !class_exists($this->model)) ||
            (\is_object($this->model) &&
                !method_exists($this->model, 'getTable'))
        ) {
            throw new \InvalidArgumentException('Make sure to provide a valid Eloquent model or a
            model with getTable method that returns a string to the ["model" => ModelClass]');
        }
    }
}
