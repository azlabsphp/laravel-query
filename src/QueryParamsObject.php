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

use Drewlabs\Packages\Database\Traits\ContainerAware;

/**
 * @internal Required internally for parsing query parameter
 *           The API is subject to change as the name can change as well
 *           Therefore using it externally, may lead to breaking changes when internal decisions are made
 */
class QueryParamsObject
{
    use ContainerAware;

    /**
     * @var string|object
     */
    private $model;

    /**
     * @var string
     */
    private $column;

    public function __construct($attributes = [])
    {
        $this->model = $attributes['model'] ?? null;
        $this->column = $attributes['column'] ?? null;
        $this->validateAttributes();
    }

    /**
     * {@inheritDoc}
     */
    public function __toString()
    {
        $model = \is_string($this->model) ?
            self::createResolver($this->model)()->getTable() :
            $this->model->getTable();

        $attributes = array_merge(
            [$model],
            $this->column ? [$this->column] : []
        );

        return trim(drewlabs_core_strings_concat('.', ...$attributes));
    }

    /**
     * Returns string representation of the current object.
     *
     * @return string
     */
    public function toString()
    {
        return $this->__toString();
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
