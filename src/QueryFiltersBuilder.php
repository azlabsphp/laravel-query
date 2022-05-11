<?php

namespace Drewlabs\Packages\Database;

use Closure;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Drewlabs\Contracts\Data\Model\Model;
use Drewlabs\Packages\Database\Traits\ContainerAware;
use Drewlabs\Core\Helpers\Arr;
use Drewlabs\Core\Helpers\Functional;
use Drewlabs\Core\Helpers\Str;
use InvalidArgumentException;

class QueryFiltersBuilder
{
    use ContainerAware;

    /**
     * 
     * @var Model|Eloquent
     */
    private $model;

    private function __construct($model)
    {
        $this->model = $model;
    }

    /**
     * 
     * @param mixed $model 
     * @return self 
     */
    public static function for($model)
    {
        $model_ =  is_string($model)  ? self::createResolver($model)() : $model;
        return new self($model_);
    }

    /**
     * Creates Query filters from parameter bag. The parameter bag can be
     * an instance of {@see \Drewlabs\Contracts\Validator\ViewModel}|Laravel Http
     * request
     * 
     * @param mixed $source 
     * @return mixed 
     */
    public function build($source)
    {
        return Functional::compose(
            static function ($param) use ($source) {
                return static::filtersFromQueryParameters($param, $source);
            },
            static function ($filters) use ($source) {
                return static::filterFrom__Query($source, $filters);
            }
        )($this->model);
    }


    /**
     * 
     * @param mixed $model 
     * @param mixed $source 
     * @return array 
     */
    public static function filtersFromQueryParameters($model, $source)
    {
        $filters = [];
        if ($source->has($model->getPrimaryKey()) && null !== $source->get($model->getPrimaryKey())) {
            $filters['where'][] = [$model->getPrimaryKey(), $source->get($model->getPrimaryKey())];
        }
        foreach ($source->all() as $key => $value) {
            $searchable = array_merge($model->getFillable(), $model->getGuarded());
            if (!empty($value)) {
                if (in_array($key, $searchable, true)) {
                    [$operator, $value, $method] = static::operatorValue($value);
                    $filters[$method ?? 'orWhere'][] = [$key, $operator, $value];
                } elseif (Str::contains($key, ['__'])) {
                    [$relation, $column] = explode('__', $key);
                    $relation = Str::replace([':', '%'], '.', $relation ?? '');
                    $model_method = Str::contains($relation, '.') ? Str::before('.', $relation) : $relation;
                    if (method_exists($model, $model_method) && null !== $column) {
                        $filters['whereHas'][] = [$relation, static function ($query) use ($value, $column) {
                            if (is_array($value)) {
                                $query->whereIn($column, $value);
                            } else {
                                [$operator, $value, $method] = static::operatorValue($value);
                                $query->{$method ?? 'where'}($column, $operator, $value);
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

    /**
     * 
     * @param mixed $params_bag 
     * @param array $in 
     * @return array 
     * @throws InvalidArgumentException 
     */
    public static function filterFrom__Query($params_bag, $in = [])
    {
        $filters = $in ?? [];
        if ($params_bag->has('_query')) {
            $query = $params_bag->get('_query');
            $query = Str::isStr($query) ? json_decode($query, true) : $query;
            if (!Arr::isArrayable($query) || !Arr::isassoc($query)) {
                return $filters;
            }
            $queryMethods = static::getSupportedQueryMethods();
            foreach ($queryMethods as $method) {
                // code...
                if (!array_key_exists($method, $query)) {
                    continue;
                }
                $parsed_value = static::buildParameters($method, $query[$method]);
                if (empty($parsed_value)) {
                    continue;
                }
                if (isset($filters[$method])) {
                    if (Arr::isList($parsed_value)) {
                        foreach ($parsed_value as $current) {
                            $filters[$method][] = $current;
                        }
                    } else {
                        $filters[$method][] = $parsed_value;
                    }
                    continue;
                }
                if (!Arr::isArrayable($parsed_value)) {
                    $filters[$method] = $parsed_value;
                    continue;
                }
                $filters[$method] = array_merge($filters[$method] ?? [], $parsed_value);
            }
        }
        return $filters;
    }

    /**
     * 
     * @param mixed $method 
     * @param mixed $params 
     * @return mixed 
     * @throws InvalidArgumentException 
     */
    private static function buildParameters($method, $params)
    {
        switch ($method) {
            case 'where':
                return static::whereQueryParameters($params);
            case 'whereDate':
                return static::whereQueryParameters($params);
            case 'whereHas':
                return static::buildSubQuery($params);
            case 'whereDoesntHave':
                return static::buildSubQuery($params);
            case 'orWhere':
                return static::whereQueryParameters($params);
            case 'whereIn':
                return static::buildWhereInQuery($params);
            case 'whereNotIn':
                return static::buildWhereInQuery($params);
            case 'orderBy':
                return static::buildOrderByQuery($params);
            case 'whereNull':
                return static::buildWhereNullQuery($params);
            case 'orWhereNull':
                return static::buildWhereNullQuery($params);
            case 'whereNotNull':
                return static::buildWhereNullQuery($params);
            case 'orWhereNotNull':
                return static::buildWhereNullQuery($params);
            case 'doesntHave':
                return Arr::isArrayable($params) ? (isset($params['column']) ? [$method => $params['column']] : []) : [$method => $params];
            case 'has':
                return Arr::isArrayable($params) ? (isset($params['column']) ? [$method => $params['column']] : []) : [$method => $params];
            default:
                return [];
        }
    }

    /**
     * 
     * @param array|string $params 
     * @return array 
     */
    private static function buildWhereNullQuery($params)
    {
        // TODO : Optimize algorithm for duplicate values
        $assocParserFn = static function (array $value) use (&$assocParserFn) {
            if (Str::isStr($value)) {
                return $value;
            }
            $is_assoc = Arr::isassoc($value);
            if (!$is_assoc) {
                return array_reduce(
                    $value,
                    static function ($carry, $current) use (&$assocParserFn) {
                        $result = $current;
                        if (Arr::isArrayable($result) && Arr::isassoc($result)) {
                            $result = $assocParserFn($current);
                        }

                        return in_array($result, $carry, true) ?
                            $carry :
                            array_merge(
                                $carry,
                                Arr::isArrayable($result) ?
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
            if (Str::isStr($value)) {
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
                    return Arr::isArrayable($item);
                }
            ) === $arr;
        };
        if (Arr::isArrayable($params)) {
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

    /**
     * 
     * @param array|string $params 
     * @return array 
     * @throws InvalidArgumentException 
     */
    private static function buildOrderByQuery($params)
    {
        if (is_string($params)) {
            return ['by' => $params, 'order' => 'DESC'];
        }
        if (!Arr::isassoc($params) && !static::isAssocList($params)) {
            return array_map(function ($value) {
                return static::buildOrderByQuery($value);
            }, $params);
        }
        if (!(isset($params['by']) || isset($params['column'])) && !isset($params['order'])) {
            throw new \InvalidArgumentException('orderBy query requires column and order keys');
        }
        $by = $params['column'] ?? ($params['by'] ?? 'updated_at');
        $order = $params['order'] ?? 'DESC';
        return ['by' => $by, 'order' => $order];
    }

    private static function buildWhereInQuery(array $query)
    {
        if (!Arr::isassoc($query) && Arr::isnotassoclist($query)) {
            // The provided query parameters is an array
            return array_map(static function ($q) {
                return static::buildWhereInQuery($q);
            }, $query);
        }
        if (!Arr::isassoc($query)) {
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

    private static function whereQueryParameters(array $query)
    {
        if (!Arr::isassoc($query) && Arr::isnotassoclist($query)) {
            // The provided query parameters is an array
            return array_map(static function ($q) {
                return static::whereQueryParameters($q);
            }, $query);
        }
        if (Arr::isassoc($query) && isset($query['match'])) {
            // Parameters is an associayive array with a key called query
            return static::buildMatchQuery($query['match']);
        }

        return $query;
    }

    /**
     * 
     * @param array $params 
     * @return void 
     * @throws InvalidArgumentException 
     */
    private static function validateQueryParameters(array $params)
    {
        if (!isset($params['method']) || !isset($params['params'])) {
            throw new \InvalidArgumentException('The query object requires "method" and "params" keys');
        }
    }

    /**
     * 
     * @param array $query 
     * @return Closure 
     */
    private static function buildMatchQuery(array $query)
    {
        return static function ($q) use ($query) {
            static::validateQueryParameters($query);
            $supportedQueryMethods = static::getSupportedQueryMethods();
            if (!in_array($query['method'], $supportedQueryMethods, true)) {
                throw new \InvalidArgumentException(sprintf('Query method %s not found, ', $query['method']));
            }
            if (Arr::isnotassoclist($query['params'])) {
                call_user_func([$q, $query['method']], $query['params']);
            } else {
                call_user_func([$q, $query['method']], ...$query['params']);
            }
        };
    }

    /**
     * 
     * @param array $query 
     * @return array 
     */
    private static function buildSubQuery(array $query)
    {
        if (!Arr::isassoc($query) && Arr::isnotassoclist($query)) {
            return array_map(static function ($params) {
                return [
                    $params['column'],
                    static::buildMatchQuery($params['match']),
                ];
            }, $query);
        }
        return [
            $query['column'],
            static::buildMatchQuery($query['match']),
        ];
    }

    private static function isAssocList(array $items)
    {
        if (empty($items)) {
            return false;
        }
        $isAssociative = 0 !== \count(array_filter(array_keys($items), 'is_string'));
        return $isAssociative && Arr::isList($items);
    }

    private static function operatorValue($value)
    {
        // TODO : Add support for and/or switch
        // TODO : Add more operators support
        // We use == to represent = db comparison operator
        $operators = ['>=', '<>', '<=', '=like', '=='];
        foreach ($operators as $op) {
            if (Str::startsWith($value, $op)) {
                $value = Str::after($op, $value);
                // If the operator is a like operator, we removes any % from start and end of value
                // And append our own. We also make sure the operator is like instead of =like
                if ($op === '=like') {
                    $value = '%' . trim($value, '%') . '%';
                    $op = 'like';
                } elseif ($op === '==') {
                    $op = '=';
                }
                return [$op, $value, null];
            }
        }
        $op = is_numeric($value) || is_bool($value) ? '=' : 'like';
        if ($op === 'like') {
            $value = '%' . trim($value, '%') . '%';
        }
        return [$op, $value, null];
    }

    /**
     * 
     * @return string[] 
     */
    public static function getSupportedQueryMethods()
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
