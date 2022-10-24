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

namespace Drewlabs\Packages\Database;

use Drewlabs\Contracts\Data\Model\Model;
use Drewlabs\Core\Helpers\Arr;
use Drewlabs\Core\Helpers\Functional;
use Drewlabs\Core\Helpers\Str;
use Drewlabs\Packages\Database\Traits\ContainerAware;
use Illuminate\Database\Eloquent\Model as Eloquent;

class QueryFiltersBuilder
{
    use ContainerAware;

    /**
     * List of query operator supported by the Query Filters handler.
     *
     * @var string[]
     */
    private const QUERY_OPERATORS = ['>=', '<=', '<', '>', '<>', '=like', '=='];

    /**
     * @var Model|Eloquent
     */
    private $model;

    /**
     * Creates a new instance of QueryFiltersBuilder.
     *
     * @param mixed $model
     *
     * @return void
     */
    private function __construct($model)
    {
        $this->model = $model;
    }

    /**
     * Create an instance of QueryFiltersBuilder from an ORM model.
     *
     * @param mixed $model
     *
     * @return self
     */
    public static function for($model)
    {
        $model_ = \is_string($model) ? self::createResolver($model)() : $model;

        return new self($model_);
    }

    /**
     * Creates Query filters from parameter bag. The parameter bag can be
     * an instance of {@see \Drewlabs\Contracts\Validator\ViewModel}|Laravel Http
     * request.
     *
     * @param mixed $parametersBag
     *
     * @return array<string, mixed>
     */
    public function build($parametersBag, array $defaults = [])
    {
        return Functional::compose(
            static function ($param) use ($parametersBag, $defaults) {
                return static::filtersFromQueryParameters($param, $parametersBag, $defaults);
            },
            static function ($filters) use ($parametersBag) {
                return static::filterFrom__Query($parametersBag, $filters);
            }
        )($this->model);
    }

    /**
     * Build filters from parameter bags.
     *
     * @param object $model
     * @param object $parametersBag
     * @param array  $defaults
     *
     * @return array<string, mixed>
     */
    public static function filtersFromQueryParameters($model, $parametersBag, $defaults = [])
    {
        $filters = Arr::map($defaults ?? [], static function ($filter) {
            // We check first if the filter is an array. If the filter is an array,
            // we then we check if the array is an array of arrays (1). If case (1) resolves
            // to true, we return the filter, else we wrap the filter in an array
            return \is_array($filter) && Arr::isList($filter) ? $filter : [$filter];
        });
        if ($parametersBag->has($model->getPrimaryKey()) && null !== $parametersBag->get($model->getPrimaryKey())) {
            $filters['where'][] = [$model->getPrimaryKey(), $parametersBag->get($model->getPrimaryKey())];
        }
        foreach ($parametersBag->all() as $key => $value) {
            $list = array_merge($model->getFillable(), $model->getGuarded());
            if (\is_string($value) && Str::contains($value, '|')) {
                // For composed value, if the value is a string and contains | character we split the value using
                // the | character and foreach item in the splitted list we add a filter
                $items = \is_string($value) && Str::contains($value, '|') ? Str::split($value, '|') : $value;
                foreach ($items as $item) {
                    $filters = static::buildFiltersArray($filters, $key, $item, $list, $model);
                }
                continue;
            }
            if (!empty($value)) {
                $filters = static::buildFiltersArray($filters, $key, $value, $list, $model);
                continue;
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
     * Build query filters using '_query' property of the parameter bag.
     *
     * @param mixed $parametersBag
     * @param array $in
     *
     * @throws \InvalidArgumentException
     *
     * @return array
     */
    public static function filterFrom__Query($parametersBag, $in = [])
    {
        $filters = $in ?? [];
        if ($parametersBag->has('_query')) {
            $query = $parametersBag->get('_query');
            $query = Str::isStr($query) ? json_decode($query, true) : $query;
            if (!Arr::isArrayable($query) || !Arr::isassoc($query)) {
                return $filters;
            }
            $queryMethods = static::getSupportedQueryMethods();
            foreach ($queryMethods as $method) {
                // code...
                if (!\array_key_exists($method, $query)) {
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
     * Returns the supported query operators.
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
            'orWhereDate',
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

    /**
     * @param string $key
     * @param mixed  $value
     * @param object $model
     *
     * @return array
     */
    private static function buildFiltersArray(array $filters, $key, $value, array $list, $model)
    {
        if (\in_array($key, $list, true)) {
            [$operator, $value, $method] = static::operatorValue($value);
            $filters[$method ?? 'orWhere'][] = [$key, $operator, $value];
        } elseif (Str::contains($key, ['__'])) {
            [$relation, $column] = explode('__', $key);
            $relation = Str::replace([':', '%'], '.', $relation ?? '');
            $model_method = Str::contains($relation, '.') ? Str::before('.', $relation) : $relation;
            if (method_exists($model, $model_method) && null !== $column) {
                $filters['whereHas'][] = [$relation, static function ($query) use ($value, $column) {
                    if (\is_array($value)) {
                        $query->whereIn($column, $value);
                    } else {
                        [$operator, $value, $method] = static::operatorValue($value);
                        $query->{$method ?? 'where'}($column, $operator, $value);
                    }
                }];
            }
        }

        return $filters;
    }

    /**
     * Build queries based on list of query parameters.
     *
     * @param array $params
     *
     * @throws \InvalidArgumentException
     *
     * @return mixed
     */
    private static function buildParameters(string $method, $params)
    {
        switch ($method) {
            case 'where':
            case 'whereDate':
            case 'orWhereDate':
            case 'orWhere':
                return static::whereQueryParameters($params);
            case 'whereHas':
            case 'whereDoesntHave':
                return static::buildSubQuery($params);
            case 'whereIn':
            case 'whereNotIn':
                return static::buildWhereInQuery($params);
            case 'orderBy':
                return static::buildOrderByQuery($params);
            case 'whereNull':
            case 'orWhereNull':
            case 'whereNotNull':
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
     * Build a where null query based on params.
     *
     * @param array|string $params
     *
     * @return array
     */
    private static function buildWhereNullQuery($params)
    {
        // TODO : Optimize algorithm for duplicate values
        $assocParserFn = static function (array $value) use (&$assocParserFn) {
            if (Str::isStr($value)) {
                return $value;
            }
            $isassoc = Arr::isassoc($value);
            if (!$isassoc) {
                return array_reduce(
                    $value,
                    static function ($carry, $current) use (&$assocParserFn) {
                        $result = $current;
                        if (Arr::isArrayable($result) && Arr::isassoc($result)) {
                            $result = $assocParserFn($current);
                        }

                        return \in_array($result, $carry, true) ?
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
            if ($isassoc && !isset($value['column'])) {
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
            if (\is_array($arr)) {
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
                            if (\in_array($result, $carry, true)) {
                                return $carry;
                            }

                            return array_merge($carry, \is_array($result) ? $result : [$result]);
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
     * Build an order by query based on list of query parameters.
     *
     * @param array|string $params
     *
     * @throws \InvalidArgumentException
     *
     * @return array
     */
    private static function buildOrderByQuery($params)
    {
        if (\is_string($params)) {
            return ['by' => $params, 'order' => 'DESC'];
        }
        if (!Arr::isassoc($params) && !static::isAssocList($params)) {
            return array_map(static function ($value) {
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

    /**
     * Build a where in query based on list of query parameters.
     *
     * @throws \InvalidArgumentException
     *
     * @return array
     */
    private static function buildWhereInQuery(array $query)
    {
        if (!Arr::isassoc($query) && Arr::isnotassoclist($query)) {
            // The provided query parameters is an array
            return array_map(static function ($q) {
                return static::buildWhereInQuery($q);
            }, $query);
        }
        if (!Arr::isassoc($query)) {
            $count = \count($query);
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

    /**
     * Build a where query based on query parameter array.
     *
     * @return array|\Closure
     */
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
     * Validate query parameters.
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    private static function validateQueryParameters(array $params)
    {
        if (!isset($params['method']) || !isset($params['params'])) {
            throw new \InvalidArgumentException('The query object requires "method" and "params" keys');
        }
    }

    /**
     * Build a match query based the list of query parameters.
     *
     * @return \Closure
     */
    private static function buildMatchQuery(array $query)
    {
        return static function ($q) use ($query) {
            static::validateQueryParameters($query);
            $supportedQueryMethods = static::getSupportedQueryMethods();
            if (!\in_array($query['method'], $supportedQueryMethods, true)) {
                throw new \InvalidArgumentException(sprintf('Query method %s not found, ', $query['method']));
            }
            if (Arr::isnotassoclist($query['params'])) {
                \call_user_func([$q, $query['method']], $query['params']);
            } else {
                \call_user_func([$q, $query['method']], ...$query['params']);
            }
        };
    }

    /**
     * Build a subquery based on array of query parameters.
     *
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

    /**
     * Check if list is an associative list of list.
     *
     * @return bool
     */
    private static function isAssocList(array $items)
    {
        if (empty($items)) {
            return false;
        }
        $isAssociative = 0 !== \count(array_filter(array_keys($items), 'is_string'));

        return $isAssociative && Arr::isList($items);
    }

    /**
     * Parse the value in order to return the query method to apply and the operator
     * that is needed to be used.
     *
     * @param string $value
     *
     * @return array
     */
    private static function operatorValue($value)
    {
        // We use == to represent = db comparison operator
        [$method, $operators, $operator] = ['orWhere', static::QUERY_OPERATORS, null];

        foreach ($operators as $current) {
            // By default we apply the query with or where clause. But in case the developper pass a query string
            // with &&: or and: operator we query using the where clause
            if (Str::startsWith((string) $value, "and:$current:")) {
                [$method, $value, $operator] = ['where', Str::after("and:$current:", $value), $current];
                break;
            } elseif (Str::startsWith((string) $value, "&&:$current:")) {
                [$method, $value, $operator] = ['where', Str::after("&&:$current:", $value), $current];
                break;
            } elseif (Str::startsWith((string) $value, "$current:")) {
                [$value, $operator] = [Str::after("$current:", $value), $current];
                break;
            }
        }
        if (Str::startsWith((string) $value, 'and:')) {
            [$method, $value] = ['where', Str::after('and:', $value)];
        } elseif (Str::startsWith((string) $value, '&&:')) {
            [$method, $value] = ['where', Str::after('&&:', $value)];
        }
        $operator = $operator ?? (is_numeric($value) || \is_bool($value) ? '=' : 'like');
        // If the operator is a like operator, we removes any % from start and end of value
        // And append our own. We also make sure the operator is like instead of =like
        if (('=like' === $operator) || ('like' === $operator)) {
            [$value, $operator] = ['%'.trim($value, '%').'%', 'like'];
        } elseif ('==' === $operator) {
            $operator = '=';
        }
        $method = false !== strtotime((string) $value) ? ('orWhere' === $method ? 'orWhereDate' : 'whereDate') : $method;

        return [$operator, $value, $method];
    }
}
