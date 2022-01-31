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

namespace Drewlabs\Packages\Database\Proxy;

use Drewlabs\Contracts\Data\EnumerableQueryResult;
use Drewlabs\Contracts\Data\ModelFiltersInterface;
use Drewlabs\Packages\Database\EloquentDMLManager;
use Drewlabs\Packages\Database\Extensions\CustomQueryCriteria;
use Drewlabs\Packages\Database\Helpers\SelectQueryResult;

/**
 * @param EnumerableQueryResult|mixed $value
 *
 * @return SelectQueryResult
 */
function SelectQueryResult($value)
{
    return new SelectQueryResult($value);
}

/**
 * Provides a proxy method to {@see EloquentDMLManager} class constructor.
 *
 * @param string|object $model
 *
 * @return EloquentDMLManager
 */
function DMLManager($model)
{
    return new EloquentDMLManager(\is_string($model) ? $model : \get_class($model));
}

/**
 * Provides a proxy method to {@see ModelFiltersInterface} implementor class.
 *
 * @return ModelFiltersInterface
 */
function ModelFiltersHandler(array $queries = [])
{
    return new CustomQueryCriteria($queries ?? []);
}
