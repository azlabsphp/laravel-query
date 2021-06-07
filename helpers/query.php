<?php

use Drewlabs\Contracts\Data\Model\Model;
use Drewlabs\Contracts\Data\Model\Parseable;
use Drewlabs\Contracts\Data\Model\GuardedModel;

if (!function_exists('drewlabs_databse_parse_client_request_query_params')) {

    /**
     * Parse query provided in a client /GET request
     *
     * @param Model|GuardedModel|Parseable $model
     * @param mixed $params_bag
     * @return array|mixed
     */
    function drewlabs_databse_parse_client_request_query_params($model, $params_bag)
    {
        $filters = [];
        if ($params_bag->has($model->getPrimaryKey()) && !\is_null($params_bag->get($model->getPrimaryKey()))) {
            $filters['where'][] = array($model->getPrimaryKey(), $params_bag->get($model->getPrimaryKey()));
        }
        foreach ($params_bag->all() as $key => $value) {
            # code...
            $searchable = array_merge($model->getFillables(), $model->getGuardedAttributes());
            if (!empty($value)) {
                if (in_array($key, $searchable)) {
                    $operator = is_numeric($value) || is_bool($value) || is_integer($value) ? '=' : 'like';
                    $value = is_numeric($value) || is_bool($value) || is_integer($value) ? $value : '%' . $value . '%';
                    $filters['orWhere'][] = array($key, $operator, $value);
                } else if (\drewlabs_core_strings_contains($key, ['__'])) {
                    $exploded = \explode('__', $key);
                    $relation = $exploded[0];
                    $column = $exploded[1];
                    if (method_exists($model, $relation) && !is_null($column)) {
                        $filters['whereHas'][] = array($relation, function ($query) use ($value, $column) {
                            if (is_array($value)) {
                                $query->whereIn($column, $value);
                                return;
                            }
                            $operator = is_numeric($value) || is_bool($value) ? '=' : 'like';
                            $value = is_numeric($value) ? $value : '%' . $value . '%';
                            $query->where($column, $operator, $value);
                        });
                    }
                }
            }
        }
        // order this query method in the order of where -> whereHas -> orWhere
        // Write a better algorithm for soring
        uksort($filters, function ($prev, $curr) {
            if ($prev === 'where') {
                return -1;
            }
            if (($prev === 'whereHas') && ($curr === 'where')) {
                return 1;
            }
            if (($prev === 'whereHas') && ($curr === 'orWhere')) {
                return -1;
            }
            if ($prev === 'orWhere') {
                return 1;
            }
        });
        return $filters;
    }
}

if (!function_exists('drewlabs_databse_parse_client_request_query_input')) {

    /**
     * Operator function that takes in an object with `get()`, `all()`, `has()` methods defined, parse the _query object and return a filter
     * @param mixed $params_bag
     * @param array $in
     * @return array
     * @throws InvalidArgumentException
     */
    function drewlabs_databse_parse_client_request_query_input($params_bag, $in = [])
    {
        $filters = $in ?? [];
        if ($params_bag->has('_query')) {
            $query = $params_bag->get('_query');
            $query = \drewlabs_core_strings_is_str($query) ? json_decode($query, true) : $query;
            if (!\drewlabs_core_array_is_arrayable($query) || !\drewlabs_core_array_is_assoc($query)) {
                return $filters;
            }
            $queryMethods = \drewlabs_database_supported_query_methods();
            foreach ($queryMethods as $method) {
                # code...
                if (!\array_key_exists($method, $query)) {
                    continue;
                }
                $parsed_value = \drewlabs_database_parse_query_method_params($method, $query[$method]);
                if (empty($parsed_value)) {
                    continue;
                }
                if (!\drewlabs_core_array_is_arrayable($parsed_value)) {
                    $filters[$method] = $parsed_value;
                    continue;
                }
                $filters[$method] = array_merge(isset($filters[$method]) ? $filters[$method] : [], $parsed_value);
            }
        }
        return $filters;
    }
}

if (!function_exists('drewlabs_databse_parse_client_request_query')) {
    /**
     * Parse query provided in a client /GET request
     *
     * @param Model|GuardedModel|Parseable $model
     * @param mixed $params_bag
     * @return array|mixed
     */
    function drewlabs_databse_parse_client_request_query($model, $params_bag)
    {
        return \drewlabs_core_fn_compose(
            function ($param) use ($params_bag) {
                return \drewlabs_databse_parse_client_request_query_params($param, $params_bag);
            },
            function ($filters) use ($params_bag) {
                return \drewlabs_databse_parse_client_request_query_input($params_bag, $filters);
            }
        )(\drewlabs_core_strings_is_str($model) ? new $model : $model);
    }
}

if (!function_exists('drewlabs_database_parse_query_method_params')) {

    function drewlabs_database_parse_query_method_params($method, $params)
    {
        switch ($method) {
            case 'where':
                return \drewlabs_database_parse_client_where_query($params);
            case 'whereDate':
                return \drewlabs_database_parse_client_where_query($params);
            case 'whereHas':
                return \drewlabs_database_client_parse_subquery($params);
            case 'whereDoesntHave':
                return \drewlabs_database_client_parse_subquery($params);
            case 'orWhere':
                return \drewlabs_database_parse_client_where_query($params);
            case 'whereIn':
                return \drewlabs_database_parse_in_query($params);
            case 'whereNotIn':
                return \drewlabs_database_parse_in_query($params);
            case 'orderBy':
                return \drewlabs_database_parse_order_by_query($params);
            case 'whereNull':
                return \drewlabs_database_parse_where_null_query($params);
            case 'orWhereNull':
                return \drewlabs_database_parse_where_null_query($params);
            case 'whereNotNull':
                return \drewlabs_database_parse_where_null_query($params);
            case 'orWhereNotNull':
                return \drewlabs_database_parse_where_null_query($params);
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
     * Parse a whereNull Query into an array of parseble request query
     *
     * @param string|array $params
     * @return string
     */
    function drewlabs_database_parse_where_null_query($params)
    {
        // TODO : Optimize algorithm for duplicate values
        $assocParserFn = function (array $value) use (&$assocParserFn) {
            if (\drewlabs_core_strings_is_str($value)) {
                return $value;
            }
            $arrayIsAssoc = drewlabs_core_array_is_assoc($value);
            if (!$arrayIsAssoc) {
                return array_reduce($value, function ($carry, $current) use (&$assocParserFn) {
                    $result = $current;
                    if (\drewlabs_core_array_is_arrayable($result) && drewlabs_core_array_is_assoc($result)) {
                        $result = $assocParserFn($current);
                    }
                    return in_array($result, $carry) ? $carry : array_merge($carry, \drewlabs_core_array_is_arrayable($result) ? $result : [$result]);
                }, []);
            }
            if ($arrayIsAssoc && !isset($value['column'])) {
                throw new \InvalidArgumentException('orderBy query requires column key');
            }
            return $value['column'];
        };
        $valueParserFn = function ($value) use ($assocParserFn) {
            if (\drewlabs_core_strings_is_str($value)) {
                return $value;
            }
            return $assocParserFn($value);
        };
        if (\drewlabs_core_array_is_arrayable($params)) {
            $isArrayList = array_filter($params, function ($item) {
                return drewlabs_core_array_is_arrayable($item);
            }) === $params;
            if ($isArrayList) {
                return array_reduce($params, function ($carry, $current) use ($valueParserFn) {
                    $result = $valueParserFn($current);
                    if (in_array($result, $carry)) {
                        return $carry;
                    }
                    return array_merge($carry, $result);
                }, []);
            } else {
                return $assocParserFn($params);
            }
        }
        return $valueParserFn($params);
    }
}

if (!function_exists('drewlabs_database_parse_order_by_query')) {
    function drewlabs_database_parse_order_by_query(array $params)
    {
        if (!isset($params['column']) && !isset($params['order'])) {
            throw new \InvalidArgumentException('orderBy query requires column and order keys');
        }
        return ['by' => $params['column'], 'order' => $params['order']];
    }
}

if (!function_exists('drewlabs_database_parse_in_query')) {
    function drewlabs_database_parse_in_query(array $query)
    {
        if (!\drewlabs_core_array_is_assoc($query) && \drewlabs_core_array_is_no_assoc_array_list($query)) {
            // The provided query parameters is an array
            return array_map(function ($q) {
                return \drewlabs_database_parse_in_query($q);
            }, $query);
        }
        if (!\drewlabs_core_array_is_assoc($query)) {
            $count = count($query);
            if ($count !== 2) {
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

    function drewlabs_database_parse_client_where_query(array $query)
    {
        if (!\drewlabs_core_array_is_assoc($query) && \drewlabs_core_array_is_no_assoc_array_list($query)) {
            // The provided query parameters is an array
            return array_map(function ($q) {
                return \drewlabs_database_parse_client_where_query($q);
            }, $query);
        }
        if (\drewlabs_core_array_is_assoc($query) && isset($query['match'])) {
            // Parameters is an associayive array with a key called query
            return \drewlabs_database_build_inner_query($query['match']);
        }
        return $query;
    }
}

if (!function_exists('drewlabs_database_validate_query_object')) {

    function drewlabs_database_validate_query_object(array $params)
    {
        if (!isset($params['method']) || !isset($params['params'])) {
            throw new \InvalidArgumentException('The query object requires "method" and "params" keys');
        }
    }
}

if (!function_exists('drewlabs_database_build_inner_query')) {

    function drewlabs_database_build_inner_query(array $query)
    {
        return function ($q) use ($query) {
            \drewlabs_database_validate_query_object($query);
            $supportedQueryMethods = \drewlabs_database_supported_query_methods();
            if (!\in_array($query['method'], $supportedQueryMethods)) {
                throw new \InvalidArgumentException(sprintf('Query method %s not found, ', $query['method']));
            }
            call_user_func_array([$q, $query['method']], $query['params']);
        };
    }
}

if (!function_exists('drewlabs_database_client_parse_subquery')) {

    function drewlabs_database_client_parse_subquery(array $query)
    {
        if (!\drewlabs_core_array_is_assoc($query) && \drewlabs_core_array_is_no_assoc_array_list($query)) {
            return array_map(function ($params) {
                return [
                    $params['column'],
                    \drewlabs_database_build_inner_query($params['match'])
                ];
            }, $query);
        }
        return [
            $query['column'],
            \drewlabs_database_build_inner_query($query['match'])
        ];
    }
}

if (!function_exists('drewlabs_core_supported_query_methods')) {
    /**
     * Returns the list of query methods supported by the database package
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
            'orWhereNotNull'
        ];
    }
}
