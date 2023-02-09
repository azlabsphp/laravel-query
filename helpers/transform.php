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

use Drewlabs\Contracts\Data\EnumerableQueryResult as ContractsEnumerableQueryResult;
use Drewlabs\Packages\Database\EnumerableQueryResult;

use function Drewlabs\Support\Proxy\Collection;
use Illuminate\Container\Container;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as ContractsLengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Http\Request;

use Illuminate\Pagination\LengthAwarePaginator;

if (!function_exists('drewlabs_database_paginator_apply_callback')) {
    /**
     * Apply data transformation algorithm provided by the callback to each item of the paginator data.
     *
     * @return Paginator
     */
    function drewlabs_database_paginator_apply_callback(Paginator $item, $callback)
    {
        $request = class_exists(Request::class) ? Container::getInstance()->make('request') : null;

        return new LengthAwarePaginator(
            iterator_to_array(
                drewlabs_core_iter_filter(
                    drewlabs_core_iter_map(
                        new ArrayIterator($item->items()),
                        $callback
                    ),
                    static function ($v) {
                        return isset($v);
                    },
                    false
                )
            ),
            call_user_func([$item, 'total']),
            $item->perPage(),
            $item->currentPage(),
            [
                'path' => $request ? $request->url() : null,
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
        $request = class_exists(Request::class) ? Container::getInstance()->make('request') : null;

        return new LengthAwarePaginator(
            call_user_func($callback, collect($item->items())),
            $item instanceof ContractsLengthAwarePaginator ? $item->total() : count($transformed ?? []),
            $item->perPage(),
            $item->currentPage(),
            [
                'path' => $request ? $request->url() : null,
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
     * @param Paginator|ContractsEnumerableQueryResult $item
     *
     * @return mixed
     */
    function drewlabs_database_map_query_result($item, callable $callback)
    {
        $item = $item instanceof ContractsEnumerableQueryResult ? $item->getCollection() : $item;
        if (is_array($item)) {
            $item = Collection($item['data'] ?? []);
        }
        if ($item instanceof Paginator) {
            return drewlabs_database_paginator_apply_callback($item, $callback);
        }

        return new EnumerableQueryResult(
            $item->map($callback)
                ->filter(static function ($current) {
                    return isset($current);
                })
        );
    }
}

if (!function_exists('drewlabs_database_apply')) {
    /**
     * Transform all data by passing them to a user provided callback.
     *
     * @param Paginator|array|mixed $item
     *
     * @return Paginator|ContractsEnumerableQueryResult
     */
    function drewlabs_database_apply($item, callable $callback)
    {
        $item = $item instanceof ContractsEnumerableQueryResult ? $item->getCollection() : $item;
        if (is_array($item)) {
            $item = Collection($item['data'] ?? []);
        }
        if ($item instanceof Paginator) {
            return drewlabs_database_paginator_apply_to_all($item, $callback);
        }

        return new EnumerableQueryResult(
            call_user_func($callback, null === $item ? Collection($item) : $item)
        );
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
