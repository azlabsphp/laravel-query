<?php

namespace Drewlabs\Packages\Database\Proxy;

use Drewlabs\Packages\Database\Helpers\SelectQueryResult;
use Drewlabs\Contracts\Data\DataProviderQueryResultInterface;
use Drewlabs\Packages\Database\EloquentDMLManager;
use Drewlabs\Contracts\Data\ModelFiltersInterface;
use Drewlabs\Packages\Database\Extensions\CustomQueryCriteria;
use Illuminate\Contracts\Pagination\Paginator;

/**
 * @param Paginator|DataProviderQueryResultInterface $value
 * @return SelectQueryResult 
 */
function SelectQueryResult($value)
{
    return new SelectQueryResult($value);
}

/**
 * Provides a proxy method to {@see EloquentDMLManager} class constructor
 * 
 * @param string|object $model 
 * @return EloquentDMLManager 
 */
function DMLManager($model)
{
    return new EloquentDMLManager(is_string($model) ? $model : get_class($model));
}

/**
 * Provides a proxy method to {@see ModelFiltersInterface} implementor class
 * 
 * @return ModelFiltersInterface 
 */
function ModelFiltersHandler(array $queries = [])
{
    return new CustomQueryCriteria($queries ?? []);
}
