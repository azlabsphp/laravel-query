<?php

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Pagination\Paginator;
use Drewlabs\Contracts\Data\DataProviderQueryResultInterface;
use Drewlabs\Core\Data\DataProviderQueryResult;
use Drewlabs\Support\Collections\SimpleCollection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as ContractsLengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Enumerable;

if (!function_exists('drewlabs_database_paginator_apply_callback')) {
    /**
     * Apply data transformation algorithm provided by the callback to each item of the paginator data
     *
     * @param Paginator $item
     * @param callable $callback
     * @return Paginator
     */
    function drewlabs_database_paginator_apply_callback(Paginator $item, callable $callback)
    {
        try {
            /**
             * @var \Illuminate\Http\Request
             */
            $request =  Container::getInstance()->make('request');
        } catch (BindingResolutionException $e) {
            $request = null;
        }
        // TODO: Verify later if well implemented
        $transformed = collect(
            array_values(
                array_filter(
                    array_map($callback, $item->items()),
                    function ($v) {
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
                    'page' => $item->currentPage()
                ]
            ]
        );
    }
}

if (!function_exists('drewlabs_database_paginator_apply_to_all')) {
    /**
     * Apply data transformation algorithm provided by the callback to paginator data
     *
     * @param Paginator $item
     * @param callable $callback
     * @return Paginator
     */
    function drewlabs_database_paginator_apply_to_all(Paginator $item, callable $callback)
    {
        $result = \call_user_func_array($callback, [collect($item->items())]);
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
            $request =  Container::getInstance()->make('request');
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
                    'page' => $item->currentPage()
                ]
            ]
        );
    }
}



if (!function_exists('drewlabs_database_map_query_result')) {
    /**
     * Apply transformation to response object on a get all request
     *
     * @param Paginator|DataProviderQueryResultInterface $item
     * @param callable $callback
     * @return mixed
     */
    function drewlabs_database_map_query_result($item, callable $callback)
    {
        if ($item instanceof DataProviderQueryResultInterface) {
            $item = $item->getCollection();
        } else if (is_array($item)) {
            $item = ($value = $item['data'] ?? null) ? $value : $item;
        }
        if ($item instanceof Paginator) {
            return drewlabs_database_paginator_apply_callback($item, $callback);
        }
        if (is_array($item)) {
            $collection = new SimpleCollection(
                array_values(
                    array_filter(
                        array_map($callback, $item),
                        function ($current) {
                            return isset($current);
                        }
                    )
                )
            );
        } else {
            $collection = new SimpleCollection(
                ($item instanceof Enumerable ? $item : collect($item ?? []))
                    ->map($callback)
                    ->filter(function ($current) {
                        return isset($current);
                    })->all()
            );
        }
        return new DataProviderQueryResult($collection);
    }
}

if (!function_exists('drewlabs_database_apply')) {
    /**
     * Transform all data by passing them to a user provided callback
     *
     * @param Paginator|array|mixed $item
     * @param callable $callback
     * @return Paginator|DataProviderQueryResultInterface
     */
    function drewlabs_database_apply($item, callable $callback)
    {
        if ($item instanceof DataProviderQueryResultInterface) {
            $item = $item->getCollection();
        } else if (is_array($item)) {
            $item = ($value = $item['data'] ?? null) ? $value : $item;
        }
        if ($item instanceof Paginator) {
            return drewlabs_database_paginator_apply_to_all($item, $callback);
        }
        if (null === $item) {
            return new DataProviderQueryResult(new SimpleCollection([]));
        }
        $result = is_array($item) ? \call_user_func_array($callback, [collect($item)]) : \call_user_func_array($callback, [collect($item)]);
        return new DataProviderQueryResult(
            new SimpleCollection(
                is_array($result) ? array_values($result) : array_values($result->all())
            )
        );
    }
}

//#region Compatibility global functions
if (!function_exists('apply_callback_to_paginator_data')) {
    /**
     * Apply data transformation algorithm provided by the callback to each item of the paginator data
     *
     * @param Paginator $item
     * @param callable $callback
     * @return Paginator
     */
    function apply_callback_to_paginator_data(Paginator $item, callable $callback)
    {
        return drewlabs_database_paginator_apply_callback($item, $callback);
    }
}

if (!function_exists('map_query_result')) {
    /**
     * Apply transformation to response object on a get all request
     *
     * @param Paginator|array|mixed $item
     * @param callable $callback
     * @return mixed
     */
    function map_query_result($item, callable $callback)
    {
        return drewlabs_database_map_query_result($item, $callback);
    }
}

if (!function_exists('transform_paginator_data')) {
    /**
     * Apply data transformation algorithm provided by the callback to paginator data
     *
     * @param Paginator $item
     * @param callable $callback
     * @return Paginator
     */
    function transform_paginator_data($item, callable $callback)
    {
        return drewlabs_database_paginator_apply_to_all($item, $callback);
    }
}

if (!function_exists('transform_query_result')) {
    /**
     * Transform all data by passing them to a user provided callback
     *
     * @param Paginator|array|mixed $item
     * @param callable $callback
     * @return Paginator|DataProviderQueryResultInterface
     */
    function transform_query_result($item, callable $callback)
    {
        return drewlabs_database_apply($item, $callback);
    }
}
//#endregion Compatibility global functions