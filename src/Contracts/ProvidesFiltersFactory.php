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

namespace Drewlabs\Laravel\Query\Contracts;

use Drewlabs\Query\Contracts\FiltersBuilderInterface;

interface ProvidesFiltersFactory
{
    /**
     * Set the filters factory instance on the current instance.
     *
     * @param \Closure $factory
     *
     * @return self
     */
    public function setFiltersFactory(\Closure $factory);

    /**
     * return the filters factory instance.
     *
     * @return \Closure(mixed|array $queries): FiltersBuilderInterface
     */
    public function getFiltersFactory();
}
