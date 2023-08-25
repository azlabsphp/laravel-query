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
use Drewlabs\Support\Traits\MethodProxy;
use Illuminate\Contracts\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder;

final class EloquentQueryFilters implements FiltersInterface
{
    use MethodProxy;

    /**
     * Query filters dictionary.
     *
     * @var array
     */
    private $filters = [];

    /**
     * List of supported aggregation methods
     * 
     * @var string[]
     */
    private $aggregations = [];

    /**
     * @var string[]
     */
    const DEFAULT_AGGREGATIONS = ['min', 'avg', 'sum', 'max', 'count'];

    /**
     * Creates class instance.
     */
    public function __construct(array $values = null, array $aggregations = null)
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

        // Then is the aggregate filter is present in the list of filters, we invoke the aggregate
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
            printf("%s\n", $e->getMessage());
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

        return Arr::isList($result = (new ConditionQuery())->compile($params)) ? array_reduce($result, static function ($builder, array $query) {
            return \is_array($query) ? $builder->where(...array_values($query)) : $builder->where($query);
        }, $builder) : $builder->where(...$result);
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

        return Arr::isList($result = (new ConditionQuery())->compile($params)) ? array_reduce($result, static function ($builder, array $query) {
            // In case the internal query is not an array, we simply pass it to the illuminate query builder
            // Which may throws if the parameters are not supported
            return \is_array($query) ? $builder->orWhere(...array_values($query)) : $builder->orWhere($query);
        }, $builder) : $builder->orWhere(...$result);
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
    private function noIn($builder, array $params)
    {
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

    // #region Aggregation

    /**
     * Handle aggregation query on the filter object
     * 
     * @param mixed $builder 
     * @param mixed $params 
     * @return mixed 
     */
    private function aggregate($builder, $params)
    {
        foreach ($params as $name => $value) {
            if (!in_array($name, $this->aggregations)) {
                continue;
            }
            $builder = $this->invoke($name, $builder, $value);
        }
        return $builder;
    }

    /**
     * apply `count` query on the builder.
     *
     * @param Builder $builder
     * @param string|array $params
     *
     * @return void
     */
    private function count($builder, $params)
    {
        $params = array_map(function ($value) {
            return is_array($value) ? array_pad($value, 2, null) : [$value, null];
        }, !is_array($params) ? [$params] : $params);

        return array_reduce($params, function (Builder $builder, $current) {
            list($column, $relation) = $current;
            return null !== $relation ? $builder->withAggregate($relation, $column ?? '*', 'count') : $builder->addSelect([
                sprintf('count_%s', $column) => $builder->clone()->selectRaw(sprintf("count(%s)", $column ?? '*'))->limit(1)
            ]);
        }, $builder);
    }

    /**
     * apply `min` aggregation query on the builder.
     *
     * @param Builder $builder
     * @param array|string  $params
     *
     * @return void
     */
    private function min($builder, $params)
    {
        $params = array_map(function ($value) {
            return is_array($value) ? array_pad($value, 2, null) : [$value, null];
        }, !is_array($params) ? [$params] : $params);

        return array_reduce($params, function ($builder, $current) {
            list($column, $relation) = $current;
            return null !== $relation ? $builder->withAggregate($relation, $column ?? '*', 'min') : $builder->addSelect([
                sprintf('min_%s', $column) => $builder->clone()->selectRaw(sprintf("min(%s)", $column ?? '*'))->limit(1)
            ]);
        }, $builder);
    }

    /**
     * apply `max` aggregation query on the builder.
     *
     * @param Builder $builder
     * @param array|string  $params
     *
     * @return void
     */
    private function max($builder, $params)
    {
        $params = array_map(function ($value) {
            return is_array($value) ? array_pad($value, 2, null) : [$value, null];
        }, !is_array($params) ? [$params] : $params);

        return array_reduce($params, function ($builder, $current) {
            list($column, $relation) = $current;
            return null !== $relation ? $builder->withAggregate($relation, $column ?? '*', 'max') : $builder->addSelect([
                sprintf('max_%s', $column) => $builder->clone()->selectRaw(sprintf("max(%s)", $column ?? '*'))->limit(1)
            ]);
        }, $builder);
    }

    /**
     * apply `sum` aggregation query on the builder.
     *
     * @param Builder $builder
     * @param array|string  $params
     *
     * @return void
     */
    private function sum($builder, $params)
    {
        $params = array_map(function ($value) {
            return is_array($value) ? array_pad($value, 2, null) : [$value, null];
        }, !is_array($params) ? [$params] : $params);

        return array_reduce($params, function ($builder, $current) {
            list($column, $relation) = $current;
            return null !== $relation ? $builder->withAggregate($relation, $column ?? '*', 'sum') : $builder->addSelect([
                sprintf('sum_%s', $column) => $builder->clone()->selectRaw(sprintf("sum(%s)", $column ?? '*'))->limit(1)
            ]);
        }, $builder);
    }

    /**
     * apply `avg` aggregation query on the builder.
     *
     * @param Builder $builder
     * @param array|string  $params
     *
     * @return void
     */
    private function avg($builder, $params)
    {
        $params = array_map(function ($value) {
            return is_array($value) ? array_pad($value, 2, null) : [$value, null];
        }, !is_array($params) ? [$params] : $params);

        return array_reduce($params, function ($builder, $current) {
            list($column, $relation) = $current;
            return null !== $relation ? $builder->withAggregate($relation, $column ?? '*', 'avg') : $builder->addSelect([
                sprintf('avg_%s', $column) => $builder->clone()->selectRaw(sprintf("avg(%s)", $column ?? '*'))->limit(1)
            ]);
        }, $builder);
    }
    // #endregion Aggregation

    //#region helper methods
    private function buildNestedQueryParams(array $params, $boolean = 'and', $operator = '>=')
    {
        $opts = [$operator, 1, $boolean];
        $output = [];
        /**
         * @var callable
         */
        $callback = null;
        foreach ($params as $value) {
            if (!is_string($value) && is_callable($value)) {
                $callback = $value;
                continue;
            }
            $output[] = $value;
        }
        // We merge the output with the slice from the optional parameters value and append the callback
        // at the end if provided
        $self = $this;
        $output = [...$output, ...array_slice($opts, count($output) - 1), null !== $callback ? function ($builder) use ($callback, $self) {
            return \Closure::fromCallable($callback)->__invoke($self, $builder);
        } : $callback];
        // We protect the query method against parameters that do not translate what they means, by overriding
        // the operator and the boolean function to use for the query
        $output[1] = $operator;
        $output[3] = $boolean;
        return $output;
    }
    //#region helper methods
}
