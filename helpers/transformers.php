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

use Drewlabs\Contracts\Data\DataProviderQueryResultInterface;
use Drewlabs\Core\Data\DataProviderQueryResult;
use function Drewlabs\Support\Proxy\Collection;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as ContractsLengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;

use Illuminate\Pagination\LengthAwarePaginator;

if (!function_exists('drewlabs_database_paginator_apply_callback')) {
    /**
     * Apply data transformation algorithm provided by the callback to each item of the paginator data.
     *
     * @return Paginator
     */
    function drewlabs_database_paginator_apply_callback(Paginator $item, callable $callback)
    {
        try {
            /**
             * @var \Illuminate\Http\Request
             */
            $request = Container::getInstance()->make('request');
        } catch (BindingResolutionException $e) {
            $request = null;
        }
        // TODO: Verify later if well implemented
        $transformed = collect(
            array_values(
                array_filter(
                    array_map($callback, $item->items()),
                    static function ($v) {
                        return isset($v);
                    }
                )
            )
        );

        return new LengthAwarePaginator(
            $transformed,
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
        $result = call_user_func_array($callback, [collect($item->items())]);
        $result = $result ?
            array_values(
                is_array($result) ?
                    $result :
                    $result->all()
            ) : $result;

        try {
            /**
             * @var \Illuminate\Http\Request
             */
            $request = Container::getInstance()->make('request');
        } catch (BindingResolutionException $e) {
            $request = null;
        }

        return new LengthAwarePaginator(
            $result,
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
     * @param Paginator|DataProviderQueryResultInterface $item
     *
     * @return mixed
     */
    function drewlabs_database_map_query_result($item, callable $callback)
    {
        if ($item instanceof DataProviderQueryResultInterface) {
            $item = $item->getCollection();
        } elseif (is_array($item) || ($item instanceof \ArrayAccess)) {
            $item = ($value = $item['data'] ?? null) ? $value : $item;
        }
        if ($item instanceof Paginator) {
            return drewlabs_database_paginator_apply_callback($item, $callback);
        }

        return new DataProviderQueryResult(
            Collection($item)
                ->map($callback)
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
     * @return Paginator|DataProviderQueryResultInterface
     */
    function drewlabs_database_apply($item, callable $callback)
    {
        if ($item instanceof DataProviderQueryResultInterface) {
            $item = $item->getCollection();
        } elseif (is_array($item)) {
            $item = ($value = $item['data'] ?? null) ? $value : $item;
        }
        if ($item instanceof Paginator) {
            return drewlabs_database_paginator_apply_to_all($item, $callback);
        }

        return new DataProviderQueryResult(
            call_user_func($callback, null === $item ? Collection($item) : Collection())
        );
    }
}

//#region Compatibility global functions
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
     * @return Paginator|DataProviderQueryResultInterface
     */
    function transform_query_result($item, callable $callback)
    {
        return drewlabs_database_apply($item, $callback);
    }
}
//#endregion Compatibility global functions
