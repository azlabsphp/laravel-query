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

use Drewlabs\Query\Contracts\EnumerableResultInterface;
use Drewlabs\Core\Helpers\Arr;
use Drewlabs\Core\Helpers\Iter;
use Drewlabs\Query\EnumerableResult;

use function Drewlabs\Support\Proxy\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as AbstractLengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;

use Illuminate\Pagination\LengthAwarePaginator;

if (!function_exists('drewlabs_database_paginator_apply_callback')) {
    /**
     * Apply data transformation algorithm provided by the callback to each item of the paginator data.
     *
     * @return Paginator
     */
    function drewlabs_database_paginator_apply_callback(Paginator $item, $callback)
    {
        return new LengthAwarePaginator(
            Arr::create(Iter::filter(Iter::map(new ArrayIterator($item->items()), $callback), static function ($v) {
                return isset($v);
            }, false)),
            call_user_func([$item, 'total']),
            $item->perPage(),
            $item->currentPage(),
            [
                'path' => $item->path() ?? '/',
                'fragment' => $item->fragment(),
                'query' => [
                    'page' => $item->currentPage(),
                ],
            ]
        );
    }
}

if (!function_exists('drewlabs_database_paginator_apply_to_all')) {
    /**
     * Apply data transformation algorithm provided by the callback to paginator data.
     *
     * @return Paginator
     */
    function drewlabs_database_paginator_apply_to_all(Paginator $item, callable $callback)
    {
        return new LengthAwarePaginator(
            call_user_func($callback, collect($item->items())),
            $item instanceof AbstractLengthAwarePaginator ? $item->total() : count($transformed ?? []),
            $item->perPage(),
            $item->currentPage(),
            [
                'path' => $item->path() ?? '/',
                'fragment' => $item->fragment(),
                'query' => [
                    'page' => $item->currentPage(),
                ],
            ]
        );
    }
}

if (!function_exists('drewlabs_database_map_query_result')) {
    /**
     * Apply transformation to response object on a get all request.
     *
     * @param Paginator|EnumerableResultInterface $item
     *
     * @return mixed
     */
    function drewlabs_database_map_query_result($item, callable $callback)
    {
        $item = $item instanceof EnumerableResultInterface ? $item->getCollection() : $item;
        if (is_array($item)) {
            $item = Collection($item['data'] ?? []);
        }
        if ($item instanceof Paginator) {
            return drewlabs_database_paginator_apply_callback($item, $callback);
        }
        return new EnumerableResult($item->map($callback)
            ->filter(static function ($current) {
                return isset($current);
            }));
    }
}

if (!function_exists('drewlabs_database_apply')) {
    /**
     * Transform all data by passing them to a user provided callback.
     *
     * @param Paginator|array|mixed $item
     *
     * @return Paginator|EnumerableResultInterface
     */
    function drewlabs_database_apply($item, callable $callback)
    {
        $item = $item instanceof EnumerableResultInterface ? $item->getCollection() : $item;
        if (is_array($item)) {
            $item = Collection($item['data'] ?? []);
        }
        if ($item instanceof Paginator) {
            return drewlabs_database_paginator_apply_to_all($item, $callback);
        }

        return new EnumerableResult(call_user_func($callback, null === $item ? Collection($item) : $item));
    }
}

// #region Compatibility global functions
if (!function_exists('apply_callback_to_paginator_data')) {
    /**
     * Apply data transformation algorithm provided by the callback to each item of the paginator data.
     *
     * @return Paginator
     */
    function apply_callback_to_paginator_data(Paginator $item, callable $callback)
    {
        return drewlabs_database_paginator_apply_callback($item, $callback);
    }
}

if (!function_exists('map_query_result')) {
    /**
     * Apply transformation to response object on a get all request.
     *
     * @param Paginator|array|mixed $item
     *
     * @return mixed
     */
    function map_query_result($item, callable $callback)
    {
        return drewlabs_database_map_query_result($item, $callback);
    }
}

if (!function_exists('transform_paginator_data')) {
    /**
     * Apply data transformation algorithm provided by the callback to paginator data.
     *
     * @param Paginator $item
     *
     * @return Paginator
     */
    function transform_paginator_data($item, callable $callback)
    {
        return drewlabs_database_paginator_apply_to_all($item, $callback);
    }
}

if (!function_exists('transform_query_result')) {
    /**
     * Transform all data by passing them to a user provided callback.
     *
     * @param Paginator|array|mixed $item
     *
     * @return Paginator|ContractsEnumerableQueryResult
     */
    function transform_query_result($item, callable $callback)
    {
        return drewlabs_database_apply($item, $callback);
    }
}
// #endregion Compatibility global functions
