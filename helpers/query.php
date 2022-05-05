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

use Drewlabs\Contracts\Data\Model\GuardedModel;
use Drewlabs\Contracts\Data\Model\Model;
use Drewlabs\Contracts\Data\Model\Parseable;

if (!function_exists('drewlabs_databse_parse_client_request_query_params')) {

    /**
     * Parse query provided in a client /GET request.
     *
     * @param Model|GuardedModel|Parseable $model
     * @param mixed                        $params_bag
     *
     * @throws \InvalidArgumentException
     *
     * @return array|mixed
     */
    function drewlabs_databse_parse_client_request_query_params($model, $params_bag)
    {
        $filters = [];
        if ($params_bag->has($model->getPrimaryKey()) && null !== $params_bag->get($model->getPrimaryKey())) {
            $filters['where'][] = [$model->getPrimaryKey(), $params_bag->get($model->getPrimaryKey())];
        }
        foreach ($params_bag->all() as $key => $value) {
            // code...
            $searchable = array_merge($model->getFillable(), $model->getGuarded());
            if (!empty($value)) {
                if (in_array($key, $searchable, true)) {
                    $operator = is_numeric($value) || is_bool($value) || is_int($value) ? '=' : 'like';
                    $value = is_numeric($value) || is_bool($value) || is_int($value) ? $value : '%'.$value.'%';
                    $filters['orWhere'][] = [$key, $operator, $value];
                } elseif (drewlabs_core_strings_contains($key, ['__'])) {
                    [$relation, $column] = explode('__', $key);
                    $relation = drewlabs_core_strings_replace([':', '%'], '.', $relation ?? '');
                    $model_method = drewlabs_core_strings_contains($relation, '.') ? drewlabs_core_strings_before('.', $relation) : $relation;
                    if (method_exists($model, $model_method) && null !== $column) {
                        $filters['whereHas'][] = [$relation, static function ($query) use ($value, $column) {
                            if (is_array($value)) {
                                $query->whereIn($column, $value);
                            } else {
                                $operator = is_numeric($value) || is_bool($value) ? '=' : 'like';
                                $value = is_numeric($value) ? $value : '%'.$value.'%';
                                $query->where($column, $operator, $value);
                            }
                        }];
                    }
                }
            }
        }
        // order this query method in the order of where -> whereHas -> orWhere
        // Write a better algorithm for soring
        uksort($filters, static function ($prev, $curr) {
            if ('where' === $prev) {
                return -1;
            }
            if (('whereHas' === $prev) && ('where' === $curr)) {
                return 1;
            }
            if (('whereHas' === $prev) && ('orWhere' === $curr)) {
                return -1;
            }
            if ('orWhere' === $prev) {
                return 1;
            }
        });

        return $filters;
    }
}

if (!function_exists('drewlabs_databse_parse_client_request_query_input')) {

    /**
     * Operator function that takes in an object with `get()`, `all()`, `has()` methods defined, parse the _query object and return a filter.
     *
     * @param mixed $params_bag
     * @param array $in
     *
     * @throws InvalidArgumentException
     *
     * @return array
     */
    function drewlabs_databse_parse_client_request_query_input($params_bag, $in = [])
    {
        $filters = $in ?? [];
        if ($params_bag->has('_query')) {
            $query = $params_bag->get('_query');
            $query = drewlabs_core_strings_is_str($query) ? json_decode($query, true) : $query;
            if (!drewlabs_core_array_is_arrayable($query) || !drewlabs_core_array_is_assoc($query)) {
                return $filters;
            }
            $queryMethods = drewlabs_database_supported_query_methods();
            foreach ($queryMethods as $method) {
                // code...
                if (!array_key_exists($method, $query)) {
                    continue;
                }
                $parsed_value = drewlabs_database_parse_query_method_params($method, $query[$method]);
                if (empty($parsed_value)) {
                    continue;
                }
                if (!drewlabs_core_array_is_arrayable($parsed_value)) {
                    $filters[$method] = $parsed_value;
                    continue;
                }
                $filters[$method] = array_merge($filters[$method] ?? [], $parsed_value);
            }
        }

        return $filters;
    }
}

if (!function_exists('drewlabs_databse_parse_client_request_query')) {
    /**
     * Parse query provided in a client /GET request.
     *
     * @param Model|GuardedModel|Parseable $model
     * @param mixed                        $params_bag
     *
     * @throws \InvalidArgumentException
     *
     * @return array|mixed
     */
    function drewlabs_databse_parse_client_request_query($model, $params_bag)
    {
        return drewlabs_core_fn_compose(
            static function ($param) use ($params_bag) {
                return drewlabs_databse_parse_client_request_query_params($param, $params_bag);
            },
            static function ($filters) use ($params_bag) {
                return drewlabs_databse_parse_client_request_query_input($params_bag, $filters);
            }
        )(drewlabs_core_strings_is_str($model) ? new $model() : $model);
    }
}

if (!function_exists('drewlabs_database_parse_query_method_params')) {

    /**
     * Parse the query parameters based on the matching method.
     *
     * @param string                      $method
     * @param string|array<string, mixed> $params
     *
     * @throws \InvalidArgumentException
     *
     * @return \Closure|array<string, mixed>
     */
    function drewlabs_database_parse_query_method_params($method, $params)
    {
        switch ($method) {
            case 'where':
                return drewlabs_database_parse_client_where_query($params);
            case 'whereDate':
                return drewlabs_database_parse_client_where_query($params);
            case 'whereHas':
                return drewlabs_database_client_parse_subquery($params);
            case 'whereDoesntHave':
                return drewlabs_database_client_parse_subquery($params);
            case 'orWhere':
                return drewlabs_database_parse_client_where_query($params);
            case 'whereIn':
                return drewlabs_database_parse_in_query($params);
            case 'whereNotIn':
                return drewlabs_database_parse_in_query($params);
            case 'orderBy':
                return drewlabs_database_parse_order_by_query($params);
            case 'whereNull':
                return drewlabs_database_parse_where_null_query($params);
            case 'orWhereNull':
                return drewlabs_database_parse_where_null_query($params);
            case 'whereNotNull':
                return drewlabs_database_parse_where_null_query($params);
            case 'orWhereNotNull':
                return drewlabs_database_parse_where_null_query($params);
            case 'doesntHave':
                return drewlabs_core_array_is_arrayable($params) ? (isset($params['column']) ? [$method => $params['column']] : []) : [$method => $params];
            case 'has':
                return drewlabs_core_array_is_arrayable($params) ? (isset($params['column']) ? [$method => $params['column']] : []) : [$method => $params];
            default:
                return [];
        }
    }
}

if (!function_exists('drewlabs_database_parse_where_null_query')) {
    /**
     * Parse a whereNull Query into an array of parseble request query.
     *
     * @param string|array $params
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    function drewlabs_database_parse_where_null_query($params)
    {
        // TODO : Optimize algorithm for duplicate values
        $assocParserFn = static function (array $value) use (&$assocParserFn) {
            if (drewlabs_core_strings_is_str($value)) {
                return $value;
            }
            $is_assoc = drewlabs_core_array_is_assoc($value);
            if (!$is_assoc) {
                return array_reduce(
                    $value,
                    static function ($carry, $current) use (&$assocParserFn) {
                        $result = $current;
                        if (drewlabs_core_array_is_arrayable($result) && drewlabs_core_array_is_assoc($result)) {
                            $result = $assocParserFn($current);
                        }

                        return in_array($result, $carry, true) ?
                            $carry :
                            array_merge(
                                $carry,
                                drewlabs_core_array_is_arrayable($result) ?
                                    $result :
                                    [$result]
                            );
                    },
                    []
                );
            }
            if ($is_assoc && !isset($value['column'])) {
                throw new \InvalidArgumentException('orderBy query requires column key');
            }

            return $value['column'] ?? $value[0] ?? null;
        };
        $valueParserFn = static function ($value) use ($assocParserFn) {
            if (drewlabs_core_strings_is_str($value)) {
                return $value;
            }

            return $assocParserFn($value);
        };

        $removeNull = static function ($arr) {
            if (is_array($arr)) {
                return array_filter($arr, static function ($value_) {
                    return null !== $value_;
                });
            }

            return $arr;
        };
        $isArrayList = static function ($arr) {
            return array_filter(
                $arr,
                static function ($item) {
                    return drewlabs_core_array_is_arrayable($item);
                }
            ) === $arr;
        };
        if (drewlabs_core_array_is_arrayable($params)) {
            if ($isArrayList($params)) {
                return $removeNull(
                    array_reduce(
                        $params,
                        static function ($carry, $current) use ($valueParserFn) {
                            $result = $valueParserFn($current);
                            if (in_array($result, $carry, true)) {
                                return $carry;
                            }

                            return array_merge($carry, is_array($result) ? $result : [$result]);
                        },
                        []
                    )
                );
            } else {
                return $removeNull($assocParserFn($params));
            }
        }

        return $removeNull($valueParserFn($params));
    }
}

if (!function_exists('drewlabs_database_parse_order_by_query')) {
    /**
     * Provide a where method parameters parsing implementations.
     *
     * @throws \InvalidArgumentException
     *
     * @return array<string, string>
     */
    function drewlabs_database_parse_order_by_query(array $params)
    {
        if (!(isset($params['by']) || isset($params['column'])) && !isset($params['order'])) {
            throw new \InvalidArgumentException('orderBy query requires column and order keys');
        }
        $by = $params['column'] ?? ($params['by'] ?? 'updated_at');
        $order = $params['order'] ?? 'DESC';

        return ['by' => $by, 'order' => $order];
    }
}

if (!function_exists('drewlabs_database_parse_in_query')) {
    /**
     * Generate an in query configuration.
     *
     * @throws \InvalidArgumentException
     *
     * @return array<mixed, mixed>
     */
    function drewlabs_database_parse_in_query(array $query)
    {
        if (!drewlabs_core_array_is_assoc($query) && drewlabs_core_array_is_no_assoc_array_list($query)) {
            // The provided query parameters is an array
            return array_map(static function ($q) {
                return drewlabs_database_parse_in_query($q);
            }, $query);
        }
        if (!drewlabs_core_array_is_assoc($query)) {
            $count = count($query);
            if (2 !== $count) {
                throw new \InvalidArgumentException('whereNotIn | whereIn query require 2 items first one being the column name and second being the matching array, when not using associative array like ["column" => "col", "match" => $items]');
            }

            return [$query[0], $query[1]];
        }
        if (!isset($query['column']) && !isset($query['match'])) {
            throw new \InvalidArgumentException('Outer whereIn | whereNotIn query requires column key and match key');
        }

        return [$query['column'], $query['match']];
    }
}

if (!function_exists('drewlabs_database_parse_client_where_query')) {

    /**
     * Parse a where method query parameters and return an array parameter
     * that can be used by a {FiltersInterface} implementation that takes an
     * array as parameter.
     *
     * @throws \InvalidArgumentException
     *
     * @return array
     */
    function drewlabs_database_parse_client_where_query(array $query)
    {
        if (!drewlabs_core_array_is_assoc($query) && drewlabs_core_array_is_no_assoc_array_list($query)) {
            // The provided query parameters is an array
            return array_map(static function ($q) {
                return drewlabs_database_parse_client_where_query($q);
            }, $query);
        }
        if (drewlabs_core_array_is_assoc($query) && isset($query['match'])) {
            // Parameters is an associayive array with a key called query
            return drewlabs_database_build_inner_query($query['match']);
        }

        return $query;
    }
}

if (!function_exists('drewlabs_database_validate_query_object')) {

    /**
     * Validate a query object passed in the request as parameter.
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    function drewlabs_database_validate_query_object(array $params)
    {
        if (!isset($params['method']) || !isset($params['params'])) {
            throw new \InvalidArgumentException('The query object requires "method" and "params" keys');
        }
    }
}

if (!function_exists('drewlabs_database_build_inner_query')) {

    /**
     * Subquery parameter parser that generate a query Closure.
     *
     * @throws \InvalidArgumentException
     *
     * @return \Closure
     */
    function drewlabs_database_build_inner_query(array $query)
    {
        return static function ($q) use ($query) {
            drewlabs_database_validate_query_object($query);
            $supportedQueryMethods = drewlabs_database_supported_query_methods();
            if (!in_array($query['method'], $supportedQueryMethods, true)) {
                throw new \InvalidArgumentException(sprintf('Query method %s not found, ', $query['method']));
            }
            if (drewlabs_core_array_is_no_assoc_array_list($query['params'])) {
                call_user_func([$q, $query['method']], $query['params']);
            } else {
                call_user_func([$q, $query['method']], ...$query['params']);
            }
        };
    }
}

if (!function_exists('drewlabs_database_client_parse_subquery')) {

    /**
     * Parse client provided query parameters and return a parameter array
     * for the matching column along with the query Closure.
     *
     * @throws \InvalidArgumentException
     *
     * @return array<string, \Closure>
     */
    function drewlabs_database_client_parse_subquery(array $query)
    {
        if (!drewlabs_core_array_is_assoc($query) && drewlabs_core_array_is_no_assoc_array_list($query)) {
            return array_map(static function ($params) {
                return [
                    $params['column'],
                    drewlabs_database_build_inner_query($params['match']),
                ];
            }, $query);
        }

        return [
            $query['column'],
            drewlabs_database_build_inner_query($query['match']),
        ];
    }
}

if (!function_exists('drewlabs_core_supported_query_methods')) {
    /**
     * Returns the list of query methods supported by the database package.
     *
     * @return array
     */
    function drewlabs_database_supported_query_methods()
    {
        // Returns the supported query method in most valuable to the the less valuable order
        return [
            'where',
            'whereHas',
            'whereDoesntHave',
            'whereDate',
            'has',
            'doesntHave',
            'whereIn',
            'whereNotIn',
            // Added where between query
            'whereBetween',
            'orWhere',
            'orderBy',
            'groupBy',
            'skip',
            'take',
            // Supporting joins queries
            'join',
            'rightJoin',
            'leftJoin',
            // Supporting whereNull and whereNotNull queries
            'whereNull',
            'orWhereNull',
            'whereNotNull',
            'orWhereNotNull',
        ];
    }
}
