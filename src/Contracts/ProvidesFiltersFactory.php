<?php

declare(strict_types=1);

/*
 * This file is part of the drewlabs namespace.
 *
 * (c) Sidoine Azandrew <azandrewdevelopper@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drewlabs\LaravelQuery\Contracts;

interface ProvidesFiltersFactory
{
    /**
     * Set the filters factory instance on the current instance.
     *
     * @param Closure $factory
     *
     * @return self
     */
    public function setFiltersFactory(\Closure $factory);

    /**
     * Return the filters factory instance.
     *
     * @return Closure(mixed|array $queries): EloquentQueryFilters
     */
    public function getFiltersFactory();
}
