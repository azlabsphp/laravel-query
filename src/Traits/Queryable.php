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

namespace Drewlabs\Packages\Database\Traits;

use function Drewlabs\Packages\Database\Proxy\DMLManager;

/**
 * Mixin that offers methods for interacting with application database using the 
 * `DMLManager` proxy function
 * 
 * **Note**
 * The implementation assume the mixin class have a property method 
 * `getModel()` to make it easily work with view model classes.
 
 * @method \Drewlabs\Contracts\Data\Model\Model|\Illuminate\Database\Eloquent\Model|mixed           create(array $attributes, \Closure $callback = null)
 * @method \Drewlabs\Contracts\Data\Model\Model|\Illuminate\Database\Eloquent\Model|mixed           create(array $attributes, $params, bool $batch, \Closure $callback = null)
 * @method \Drewlabs\Contracts\Data\Model\Model|\Illuminate\Database\Eloquent\Model|mixed           create(array $attributes, $params = [], \Closure $callback)
 * @method bool                                                                                     delete(int $id)
 * @method bool                                                                                     delete(string $id)
 * @method int                                                                                      delete(array $query)
 * @method int                                                                                      delete(array $query, bool $batch)
 * @method \Drewlabs\Contracts\Data\EnumerableQueryResult|mixed                                     select()
 * @method \Drewlabs\Contracts\Data\Model\Model|\Illuminate\Database\Eloquent\Model|mixed           select(string $id, array $columns, \Closure $callback = null)
 * @method \Drewlabs\Contracts\Data\Model\Model|\Illuminate\Database\Eloquent\Model|mixed           select(string $id, \Closure $callback = null)
 * @method \Drewlabs\Contracts\Data\Model\Model|\Illuminate\Database\Eloquent\Model|mixed           select(int $id, array $columns, \Closure $callback = null)
 * @method \Drewlabs\Contracts\Data\Model\Model|\Illuminate\Database\Eloquent\Model|mixed           select(int $id, \Closure $callback = null)
 * @method \Drewlabs\Contracts\Data\EnumerableQueryResult|mixed                                     select(array $query, \Closure $callback = null)
 * @method \Drewlabs\Contracts\Data\EnumerableQueryResult|mixed                                     select(array $query, array $columns, \Closure $callback = null)
 * @method mixed                                                                                    select(array $query, int $per_page, int $page = null, \Closure $callback = null)
 * @method mixed                                                                                    select(array $query, int $per_page, array $columns, int $page = null, \Closure $callback = null)
 * @method int                                                                                      selectAggregate(array $query = [], string $aggregation = \Drewlabs\Packages\Database\AggregationMethods::COUNT)
 * @method int                                                                                      update(array $query, $attributes = [])
 * @method int                                                                                      update(array $query, $attributes = [], bool $bulk)
 * @method \Drewlabs\Contracts\Data\Model\Model|\Illuminate\Database\Eloquent\Model|mixed           update(int $id, $attributes, \Closure $callback = null)
 * @method \Drewlabs\Contracts\Data\Model\Model|\Illuminate\Database\Eloquent\Model|mixed           update(int $id, $attributes, $params, \Closure $callback = null)
 * @method \Drewlabs\Contracts\Data\Model\Model|\Illuminate\Database\Eloquent\Model|mixed           update(string $id, $attributes, \Closure $callback = null)
 * @method \Drewlabs\Contracts\Data\Model\Model|\Illuminate\Database\Eloquent\Model|mixed           update(string $id, $attributes, $params, \Closure $callback = null)
 */
trait Queryable
{
    public function create(...$args)
    {
        return DMLManager($this->getModel())->create(...$args);
    }

    public function delete(...$args)
    {
        return DMLManager($this->getModel())->delete(...$args);
    }

    public function select(...$args)
    {
        return DMLManager($this->getModel())->select(...$args);
    }

    public function update(...$args)
    {
        return DMLManager($this->getModel())->update(...$args);
    }

    public function aggregate(array $query = [], string $method = 'count')
    {
        return DMLManager($this->getModel())->selectAggregate($query, $method);
    }
}
