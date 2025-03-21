<?php

declare(strict_types=1);

/*
 * This file is part of the drewlabs namespace.
 *
 * (c) Sidoine Azandrew <azandrewdevelopper@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drewlabs\Laravel\Query;

use Drewlabs\Core\Helpers\Arr;
use Drewlabs\Query\ConditionQuery;
use Drewlabs\Query\Contracts\FiltersInterface;
use Drewlabs\Query\JoinQuery;
use Drewlabs\Query\QueryStatement;
use Drewlabs\Support\Traits\MethodProxy;
use Illuminate\Contracts\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder;

/**
 * @internal
 */
final class QueryFilters implements FiltersInterface
{
    use MethodProxy;

    /**
     * @var string[]
     */
    public const DEFAULT_AGGREGATIONS = [
        'min',
        'avg',
        'max',
        'count',
        'addCount',
        'sum',
        'addSum',
    ];

    /** @var array<string,string> */
    public const ELOQUENT_QUERY_PROXIES = [
        'and' => 'where',
        'where' => 'where',

        // or clause
        'or' => 'orwhere',
        'orWhere' => 'orwhere',
        'orwhere' => 'orwhere',

        // in clause
        'in' => 'whereIn',
        'whereIn' => 'whereIn',
        'wherein' => 'whereIn',

        //  not in clause
        'notIn' => 'whereNotIn',
        'notin' => 'whereNotIn',
        'whereNotIn' => 'whereNotIn',
        'wherenotin' => 'whereNotIn',

        // null clause
        'isNull' => 'whereNull',
        'isnull' => 'whereNull',
        'whereNull' => 'whereNull',
        'wherenull' => 'whereNull',

        // or null clause
        'orIsNull' => 'orwherenull',
        'orisnull' => 'orwherenull',
        'orWhereNull' => 'orwherenull',
        'orwherenull' => 'orwherenull',

        // not null clause
        'notNull' => 'whereNotNull',
        'notnull' => 'whereNotNull',
        'whereNotNull' => 'whereNotNull',
        'wherenotnull' => 'whereNotNull',

        // or not null clause
        'orNotNull' => 'orWhereNotNull',
        'ornotnull' => 'orWhereNotNull',
        'orWhereNotNull' => 'orWhereNotNull',
        'orwherenotnull' => 'orWhereNotNull',
    ];

    /**
     * Query filters dictionary.
     *
     * @var array
     */
    private $filters = [];

    /**
     * List of supported aggregation methods.
     *
     * @var string[]
     */
    private $aggregations = [];

    /**
     * Creates class instance.
     */
    public function __construct(?array $values = null, ?array $aggregations = null)
    {
        $this->setQueryFilters($values ?? []);
        $this->aggregations = !empty($aggregations) ? $aggregations : self::DEFAULT_AGGREGATIONS;
    }

    public function __call(string $method, $arguments)
    {
        [$builder, $args] = [$arguments[0] ?? null, \array_slice($arguments, 1)];

        return $this->proxy($builder, $method, $args);
    }

    /**
     * apply `select` query on query builder.
     *
     * @param QueryBuilder|Builder $builder
     * @param string[]             $columns
     *
     * @throws \InvalidArgumentException
     *
     * @return QueryBuilder|Builder
     */
    public function select($builder, ...$columns)
    {
        return $builder->select($columns);
    }

    /**
     * Creates new class instance.
     *
     * @return self
     */
    public static function new(array $values)
    {
        return new self($values);
    }

    /**
     * {@inheritDoc}
     *
     * @return QueryBuilder|Builder
     */
    public function call($builder)
    {
        // To prevent the aggregation query to be invoked first, we remove aggregate filter
        // from the list of filters before we proceed with the loop on the filters
        $filters = Arr::except($this->filters ?? [], ['aggregate']);
        foreach ($filters as $name => $value) {
            if (null !== $value) {
                $builder = \call_user_func([$this, $name], $builder, $value);
            }
        }

        // Then if the aggregate filter is present in the list of filters, we invoke the aggregate
        // filter on the builder
        if (isset($this->filters['aggregate']) && (null !== $this->filters['aggregate'])) {
            $builder = \call_user_func([$this, 'aggregate'], $builder, $this->filters['aggregate']);
        }

        return $builder;
    }

    /**
     * {@inheritDoc}
     *
     * @return QueryBuilder|Builder
     */
    public function apply($builder)
    {
        return $this->call($builder);
    }

    public function setQueryFilters(array $list)
    {
        $this->filters = $list;

        return $this;
    }

    public function invoke(string $method, $builder, $args)
    {
        try {
            return $this->{$method}($builder, $args);
        } catch (\Throwable $e) {
            return $builder;
        }
    }

    /**
     * apply `where` query on query builder.
     *
     * @param QueryBuilder|Builder $builder
     * @param mixed                $params
     *
     * @throws \InvalidArgumentException
     *
     * @return QueryBuilder|Builder
     */
    private function and($builder, $params)
    {
        if ($params instanceof \Closure) {
            return $builder->where(function ($query) use ($params) {
                return $params($this, $query);
            });
        }

        // Return the builder instance case the params is empty
        if (empty($params)) {
            return $builder;
        }

        return 0 !== \count(array_filter($result = (new ConditionQuery())->compile($params), 'is_array')) ? array_reduce($result, function ($builder, $query) {
            return $this->callWhereQuery($builder, $query);
        }, $builder) : $this->callWhereQuery($builder, $result);
    }

    /**
     * apply `has` query on the builder instance.
     *
     * @param QueryBuilder|Builder $builder
     * @param mixed                $params
     *
     * @return Builder|QueryBuilder
     */
    private function exists($builder, $params)
    {
        // Return the builder instance case the params is empty
        if (empty($params)) {
            return $builder;
        }

        $params = array_filter($params, 'is_array') === $params ? $params : [$params];

        foreach ($params as $value) {
            // To avoid query to throw, we check if the count of parameter isn't less than 2
            // Case it's less than 2 we skip to the next iteration
            if (!\is_array($value) || empty($value)) {
                continue;
            }
            $queryParams = $this->buildNestedQueryParams($value);
            $builder = $builder->has(...$queryParams);
        }

        return $builder;
    }

    /**
     * apply `orHas` query on the builder instance.
     *
     * @param QueryBuilder|Builder $builder
     * @param mixed                $params
     *
     * @return Builder|QueryBuilder
     */
    private function orExists($builder, $params)
    {
        // Return the builder instance case the params is empty
        if (empty($params)) {
            return $builder;
        }

        $params = array_filter($params, 'is_array') === $params ? $params : [$params];

        foreach ($params as $value) {
            // To avoid query to throw, we check if the count of parameter isn't less than 2
            // Case it's less than 2 we skip to the next iteration
            if (!\is_array($value) || empty($value)) {
                continue;
            }
            $queryParams = $this->buildNestedQueryParams($value, 'or');
            $builder = $builder->has(...$queryParams);
        }

        return $builder;
    }

    /**
     * apply `doesntHave` or `doesntHave` on builder instance.
     *
     * @param QueryBuilder|Builder $builder
     * @param mixed                $params
     *
     * @return QueryBuilder|Builder
     */
    private function notExists($builder, $params)
    {
        // Return the builder instance case the params is empty
        if (empty($params)) {
            return $builder;
        }

        $params = array_filter($params, 'is_array') === $params ? $params : [$params];

        foreach ($params as $value) {
            // To avoid query to throw, we check if the count of parameter isn't less than 2
            // Case it's less than 2 we skip to the next iteration
            if (!\is_array($value) || empty($value)) {
                continue;
            }
            $queryParams = $this->buildNestedQueryParams($value, 'and', '<');
            $builder = $builder->has(...$queryParams);
        }

        return $builder;
    }

    /**
     * apply `orDoesntHave` or `doesntHave` on builder instance.
     *
     * @param QueryBuilder|Builder $builder
     * @param mixed                $params
     *
     * @return QueryBuilder|Builder
     */
    private function orNotExists($builder, $params)
    {
        // Return the builder instance case the params is empty
        if (empty($params)) {
            return $builder;
        }

        $params = array_filter($params, 'is_array') === $params ? $params : [$params];

        foreach ($params as $value) {
            // To avoid query to throw, we check if the count of parameter isn't less than 2
            // Case it's less than 2 we skip to the next iteration
            if (!\is_array($value) || empty($value)) {
                continue;
            }
            $queryParams = $this->buildNestedQueryParams($value, 'or', '<');
            $builder = $builder->has(...$queryParams);
        }

        return $builder;
    }

    /**
     * apply `whereDate` on builder instance.
     *
     * @param QueryBuilder|Builder $builder
     * @param mixed                $params
     *
     * @return QueryBuilder|Builder
     */
    private function date($builder, $params)
    {
        // Return the builder instance case the params is empty
        if (empty($params)) {
            return $builder;
        }

        $params = array_filter($params, 'is_array') === $params ? $params : [$params];
        foreach ($params as $value) {
            if (!\is_array($value)) {
                continue;
            }
            $builder = $builder->whereDate(...$value);
        }

        return $builder;
    }

    /**
     * apply `orWhereDate` on builder instance.
     *
     * @param QueryBuilder|Builder $builder
     * @param mixed                $params
     *
     * @return QueryBuilder|Builder
     */
    private function orDate($builder, $params)
    {
        // Return the builder instance case the params is empty
        if (empty($params)) {
            return $builder;
        }

        $params = array_filter($params, 'is_array') === $params ? $params : [$params];
        foreach ($params as $value) {
            if (!\is_array($value)) {
                continue;
            }
            $builder = $builder->orWhereDate(...$value);
        }

        return $builder;
    }

    /**
     * apply `orWhere` on builder instance.
     *
     * @param QueryBuilder|Builder $builder
     * @param mixed                $params
     *
     * @throws \InvalidArgumentException
     *
     * @return QueryBuilder|Builder
     */
    private function or($builder, $params)
    {
        if ($params instanceof \Closure) {
            return $builder->where(function ($query) use ($params) {
                return $params($this, $query);
            });
        }

        // Return the builder instance case the params is empty
        if (empty($params)) {
            return $builder;
        }

        return 0 !== \count(array_filter($result = (new ConditionQuery())->compile($params), 'is_array')) ? array_reduce($result, function ($builder, $query) {
            // In case the internal query is not an array, we simply pass it to the illuminate query builder
            // Which may throws if the parameters are not supported
            return $this->callOrWhereQuery($builder, $query);
        }, $builder) : $this->callOrWhereQuery($builder, $result);
    }

    /**
     * apply `whereIn` on builder instance.
     *
     * @param QueryBuilder|Builder $builder
     *
     * @return QueryBuilder|Builder
     */
    private function in($builder, array $params)
    {
        // Return the builder instance case the params is empty
        if (empty($params)) {
            return $builder;
        }

        return array_reduce(array_filter($params, 'is_array') === $params ? $params : [$params], static function ($carry, $curr) {
            // To make sure the builder does not throw we ignore any in query providing invalid
            // arguments
            return \count($curr) >= 2 ? $carry->whereIn($curr[0], $curr[1]) : $carry;
        }, $builder);
    }

    /**
     * apply `whereBetween` on builder instance.
     *
     * @param QueryBuilder|Builder $builder
     *
     * @return QueryBuilder|Builder
     */
    private function between($builder, array $params)
    {
        // Return the builder instance case the params is empty
        if (empty($params)) {
            return $builder;
        }

        return array_reduce(array_filter($params, 'is_array') === $params ? $params : [$params], static function ($builder, $curr) {
            if (\count($curr) < 2) {
                return $builder;
            }

            return $builder->whereBetween(...array_values($curr));
        }, $builder);
    }

    /**
     * apply `whereNotIn` on builder instance.
     *
     * @param QueryBuilder|Builder $builder
     *
     * @return QueryBuilder|Builder
     */
    private function notIn($builder, array $params)
    {
        // Return the builder instance case the params is empty
        if (empty($params)) {
            return $builder;
        }

        return array_reduce(array_filter($params, 'is_array') === $params ? $params : [$params], static function ($carry, $curr) {
            // To make sure the builder does not throw we ignore any in query providing invalid
            // arguments
            return \count($curr) >= 2 ? $carry->whereNotIn($curr[0], $curr[1]) : $carry;
        }, $builder);
    }

    /**
     * apply `orderBy` on builder instance.
     *
     * @param QueryBuilder|Builder $builder
     *
     * @return QueryBuilder|Builder
     */
    private function sort($builder, array $params)
    {
        // Return the builder instance case the params is empty
        if (empty($params)) {
            return $builder;
        }

        $validate = static function ($values) {
            if (empty($values)) {
                return false;
            }
            foreach ($values as $value) {
                if (!isset($value['order']) || !isset($value['by'])) {
                    return false;
                }
            }

            return true;
        };
        // Case the filters is a data structure or type [['order' => '...', 'by' => '...']]
        // we apply the filters
        if ($validate($params = Arr::isassoc($params) ? [$params] : $params)) {
            return array_reduce($params, static function ($builder, $current) {
                return $builder->orderBy($current['by'], $current['order']);
            }, $builder);
        }

        // Else we simply returns the builder without throwing any exception
        // As we consider it a falsy query parameter
        return $builder;
    }

    /**
     * apply `groupBy` on builder instance.
     *
     * @param QueryBuilder|Builder $builder
     * @param mixed                $params
     *
     * @return QueryBuilder|Builder
     */
    private function group($builder, $params)
    {
        return \is_string($params) ? $builder->groupBy($params) : $builder->groupBy(...$params);
    }

    /**
     * apply `join` query on builder instance.
     *
     * @param QueryBuilder|Builder $builder
     * @param mixed                $params
     *
     * @throws \InvalidArgumentException
     *
     * @return QueryBuilder|Builder
     */
    private function join($builder, $params)
    {
        return $this->sql_Join($builder, $params);
    }

    /**
     * apply `right join` query on builder instance.
     *
     * @param QueryBuilder|Builder $builder
     * @param mixed                $params
     *
     * @throws \InvalidArgumentException
     *
     * @return QueryBuilder|Builder
     */
    private function rightJoin($builder, $params)
    {
        return $this->sql_Join($builder, $params, 'rightJoin');
    }

    /**
     * apply `left join` query on builder instance.
     *
     * @param QueryBuilder|Builder $builder
     * @param mixed                $params
     *
     * @throws \InvalidArgumentException
     *
     * @return QueryBuilder|Builder
     */
    private function leftJoin($builder, $params)
    {
        return $this->sql_Join($builder, $params, 'leftJoin');
    }

    private function sql_Join($builder, $params, $method = 'join')
    {
        $result = (new JoinQuery())->compile($params);
        $result = Arr::isList($result) ? $result : [$result];
        foreach ($result as $value) {
            $builder = $builder->{$method}(...$value);
        }

        return $builder;
    }

    /**
     * apply `whereNull` query on builder instance.
     *
     * @param QueryBuilder|Builder $builder
     * @param mixed                $params
     *
     * @return QueryBuilder|Builder
     */
    private function isNull($builder, $params)
    {
        // Return the builder instance case the params is empty
        if (empty($params)) {
            return $builder;
        }

        $params = \is_array($params) ? $params : [$params];

        return array_reduce($params, static function ($carry, $current) {
            return $carry->whereNull($current);
        }, $builder);
    }

    /**
     * apply `whereNotNull` query on builder instance.
     *
     * @param QueryBuilder|Builder $builder
     * @param string|array         $params
     *
     * @return QueryBuilder|Builder
     */
    private function notNull($builder, $params)
    {
        // Return the builder instance case the params is empty
        if (empty($params)) {
            return $builder;
        }

        $params = \is_array($params) ? $params : [$params];

        return array_reduce($params, static function ($carry, $current) {
            return $carry->whereNotNull($current);
        }, $builder);
    }

    /**
     * apply `orWhereNull` query on builder instance.
     *
     * @param QueryBuilder|Builder $builder
     * @param string|array         $params
     *
     * @return QueryBuilder|Builder
     */
    private function orIsNull($builder, $params)
    {
        // Return the builder instance case the params is empty
        if (empty($params)) {
            return $builder;
        }

        $params = \is_array($params) ? $params : [$params];

        return array_reduce($params, static function ($carry, $current) {
            return $carry->orWhereNull($current);
        }, $builder);
    }

    /**
     * Apply `whereNotNull` query on the builder instance.
     *
     * @param Builder      $builder
     * @param string|array $params
     *
     * @return Builder
     */
    private function orNotNull($builder, $params)
    {
        // Return the builder instance case the params is empty
        if (empty($params)) {
            return $builder;
        }

        $params = \is_array($params) ? $params : [$params];

        return array_reduce($params, static function ($carry, $current) {
            return $carry->orWhereNotNull($current);
        }, $builder);
    }

    /**
     * Invoke `limit` query on the query builder.
     *
     * @param Builder $builder
     *
     * @return Builder
     */
    private function limit($builder, int $limit)
    {
        return $builder->limit($limit);
    }

    /**
     * @param Builder    $builder
     * @param array|true $columns
     *
     * @return Builder
     */
    private function distinct($builder, $columns = true)
    {
        if (\is_bool($columns)) {
            return true === $columns ? $builder->distinct() : $builder;
        }
        $columns = \is_array($columns) ? $columns : [$columns];

        // Spread the list of columns to the builder distinct function
        return $builder->distinct(...$columns);
    }

    // #region Aggregation

    /**
     * Handle aggregation query on the filter object.
     *
     * @param mixed $builder
     * @param mixed $params
     *
     * @return mixed
     */
    private function aggregate($builder, $params)
    {
        foreach ($params as $name => $value) {
            if (!\in_array($name, $this->aggregations, true)) {
                continue;
            }
            $builder = $this->invoke($name, $builder, $value);
        }

        return $builder;
    }

    /**
     * apply `count` query on the builder.
     *
     * @param Builder      $builder
     * @param string|array $params
     *
     * @return Builder
     */
    private function count($builder, $params)
    {
        // Return the builder instance case the params is empty
        if (empty($params)) {
            return $builder;
        }

        $params = array_map(static function ($value) {
            return \is_array($value) ? array_pad($value, 2, null) : [$value, null];
        }, !\is_array($params) ? [$params] : $params);

        return array_reduce($params, static function (Builder $builder, $current) {
            [$column, $relation] = $current;

            return null !== $relation ? $builder->withAggregate($relation, $column ?? '*', 'count') : $builder->addSelect([
                sprintf('count_%s', $column) => $builder->clone()->selectRaw(sprintf('count(%s)', $column ?? '*'))->limit(1),
            ]);
        }, $builder);
    }

    /**
     * Add a added_count_[column] name field which is the count of all values for the given column.
     *
     * @param Builder      $builder
     * @param string|array $p
     *
     * @return Builder
     */
    private function addCount($builder, $p)
    {
        return $this->addAggregate($builder, $p, 'COUNT');
    }

    /**
     * apply `min` aggregation query on the builder.
     *
     * @param Builder      $builder
     * @param array|string $params
     *
     * @return void
     */
    private function min($builder, $params)
    {
        // Return the builder instance case the params is empty
        if (empty($params)) {
            return $builder;
        }

        $params = array_map(static function ($value) {
            return \is_array($value) ? array_pad($value, 2, null) : [$value, null];
        }, !\is_array($params) ? [$params] : $params);

        return array_reduce($params, static function ($builder, $current) {
            [$column, $relation] = $current;

            return null !== $relation ? $builder->withAggregate($relation, $column ?? '*', 'min') : $builder->addSelect([
                sprintf('min_%s', $column) => $builder->clone()->selectRaw(sprintf('min(%s)', $column ?? '*'))->limit(1),
            ]);
        }, $builder);
    }

    /**
     * apply `max` aggregation query on the builder.
     *
     * @param Builder      $builder
     * @param array|string $params
     *
     * @return void
     */
    private function max($builder, $params)
    {
        // Return the builder instance case the params is empty
        if (empty($params)) {
            return $builder;
        }

        $params = array_map(static function ($value) {
            return \is_array($value) ? array_pad($value, 2, null) : [$value, null];
        }, !\is_array($params) ? [$params] : $params);

        return array_reduce($params, static function ($builder, $current) {
            [$column, $relation] = $current;

            return null !== $relation ? $builder->withAggregate($relation, $column ?? '*', 'max') : $builder->addSelect([
                sprintf('max_%s', $column) => $builder->clone()->selectRaw(sprintf('max(%s)', $column ?? '*'))->limit(1),
            ]);
        }, $builder);
    }

    /**
     * apply `sum` aggregation query on the builder.
     *
     * @param Builder      $builder
     * @param array|string $params
     *
     * @return void
     */
    private function sum($builder, $params)
    {
        // Return the builder instance case the params is empty
        if (empty($params)) {
            return $builder;
        }

        $params = array_map(static function ($value) {
            return \is_array($value) ? array_pad($value, 2, null) : [$value, null];
        }, !\is_array($params) ? [$params] : $params);

        return array_reduce($params, static function ($builder, $current) {
            [$column, $relation] = $current;

            return null !== $relation ? $builder->withAggregate($relation, $column ?? '*', 'sum') : $builder->addSelect([
                sprintf('sum_%s', $column) => $builder->clone()->selectRaw(sprintf('sum(%s)', $column ?? '*'))->limit(1),
            ]);
        }, $builder);
    }

    /**
     * Add a sum_by_[column] name field which is the count of all values for the given column.
     *
     * @param Builder      $builder
     * @param string|array $p
     *
     * @return Builder
     */
    private function addSum($builder, $p)
    {
        return $this->addAggregate($builder, $p, 'SUM');
    }

    /**
     * apply `avg` aggregation query on the builder.
     *
     * @param Builder      $builder
     * @param array|string $params
     *
     * @return void
     */
    private function avg($builder, $params)
    {
        // Return the builder instance case the params is empty
        if (empty($params)) {
            return $builder;
        }

        $params = array_map(static function ($value) {
            return \is_array($value) ? array_pad($value, 2, null) : [$value, null];
        }, !\is_array($params) ? [$params] : $params);

        return array_reduce($params, static function ($builder, $current) {
            [$column, $relation] = $current;

            return null !== $relation ? $builder->withAggregate($relation, $column ?? '*', 'avg') : $builder->addSelect([
                sprintf('avg_%s', $column) => $builder->clone()->selectRaw(sprintf('avg(%s)', $column ?? '*'))->limit(1),
            ]);
        }, $builder);
    }
    // #endregion Aggregation

    // #region helper methods
    private function buildNestedQueryParams(array $params, $boolean = 'and', $operator = '>=')
    {
        $opts = [$operator, 1, $boolean];
        $output = [];
        /**
         * @var callable
         */
        $callback = null;
        foreach ($params as $value) {
            if (!\is_string($value) && \is_callable($value)) {
                $callback = $value;
                continue;
            }
            $output[] = $value;
        }
        // We merge the output with the slice from the optional parameters value and append the callback
        // at the end if provided
        $self = $this;
        $output = [...$output, ...\array_slice($opts, \count($output) - 1), null !== $callback ? static function ($builder) use ($callback, $self) {
            return \Closure::fromCallable($callback)->__invoke($self, $builder);
        } : $callback];
        // We protect the query method against parameters that do not translate what they means, by overriding
        // the operator and the boolean function to use for the query
        $output[1] = $operator;
        $output[3] = $boolean;

        return $output;
    }

    /**
     * Performs an eloquent `or where` query.
     *
     * @param Builder $builder
     * @param mixed   $query
     *
     * @return Builder
     */
    private function callOrWhereQuery($builder, $query)
    {
        if (\is_array($query)) {
            $items = array_values($query);

            return 1 === \count($items) && \is_callable($items[0]) ? $builder->orWhere(function ($q) use ($items) {
                return \call_user_func_array($items[0], [$this, $q]);
            }) : $builder->orWhere(...$items);
        }
        if (\is_callable($query)) {
            return $builder->orWhere(function ($q) use ($query) {
                return $query($this, $q);
            });
        }

        return $builder->orWhere($query);
    }

    /**
     * Performs an eloquent `where` query.
     *
     * @param Builder $builder
     * @param mixed   $query
     *
     * @return Builder
     */
    private function callWhereQuery($builder, $query)
    {
        if (\is_array($query)) {
            $items = array_values($query);

            return 1 === \count($items) && \is_callable($items[0]) ? $builder->where(function ($q) use ($items) {
                return \call_user_func_array($items[0], [$this, $q]);
            }) : $builder->where(...$items);
        }
        if (\is_callable($query)) {
            return $builder->where(function ($q) use ($query) {
                return $query($this, $q);
            });
        }

        return $builder->where($query);
    }

    /**
     * @param Builder      $builder
     * @param string|array $p
     *
     * @return Builder
     */
    private function addAggregate($builder, $p, string $method = 'COUNT')
    {
        if (empty($p)) {
            return $builder;
        }

        $p = array_map(static function ($value) {
            return \is_array($value) ? array_pad($value, 3, null) : [$value, null, null];
        }, !\is_array($p) ? [$p] : $p);

        return array_reduce(!\is_array($p) ? [$p] : $p, static function (Builder $builder, $current) use ($method) {
            [$column, $query, $as] = $current;
            $queryFunc = static function ($b) {
                return $b;
            };
            if (null !== $query && \is_string($query) && !empty($query)) {
                /** @var QueryStatement[] */
                $statements = array_reduce(explode('->', $query), static function ($stmts, $val) {
                    $stmts[] = QueryStatement::fromString($val);

                    return $stmts;
                }, []);
                $queryFunc = static function ($b) use ($statements) {
                    return array_reduce($statements, static function ($carry, QueryStatement $statement) {
                        if (null === ($method = static::ELOQUENT_QUERY_PROXIES[$statement->method()] ?? null)) {
                            return $carry;
                        }

                        return \call_user_func_array([$carry, $method], $statement->args());
                    }, $b);
                };
            }
            // TODO: Uncomment the code below the old implementation if new implementation is not what
            // is intended to be done by the method
            // $expression = $builder->clone();
            // $as = $as ?? sprintf('added_%s_%s', strtolower($method), $column);
            // return $builder->addSelect([
            //      $as => $queryFunc(
            //         $expression->getConnection()
            //             ->table($expression, 't__0')
            //             ->whereColumn(sprintf("t__0.%s", $column), '=', sprintf("%s.%s", $expression->getModel()->getTable(), $column))
            //             ->selectRaw(sprintf("%s(%s)", $method, $column))
            //     )->limit(1)
            // ]);

            // TODO: Comment the code below if old implementation is preferred
            $model = $builder->getModel();
            $as = $as ?? sprintf('added_%s_%s', strtolower($method), $column);

            return $builder->addSelect([
                $as => $queryFunc(
                    $model->getConnection()
                        // We reset existing query by creating a new model query instance
                        // in order to not take in account existing filters applied on the builder
                        // instance
                        ->table($builder->getModel()->newModelQuery(), 't__0')
                        ->whereColumn(sprintf('t__0.%s', $column), '=', sprintf('%s.%s', $model->getTable(), $column))
                        ->selectRaw(sprintf('%s(%s)', $method, $column))
                )->limit(1),
            ]);
        }, $builder);
    }
    // #region helper methods
}
