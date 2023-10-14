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

use Closure;
use Drewlabs\Core\Helpers\Arr;
use Drewlabs\Laravel\Query\Concerns\CreateQueryLanguage;
use Drewlabs\Laravel\Query\Concerns\DeleteQueryLanguage;

use Drewlabs\Laravel\Query\Concerns\SelectQueryLanguage;
use Drewlabs\Laravel\Query\Concerns\UpdateQueryLanguage;
use Drewlabs\Laravel\Query\Contracts\ProvidesFiltersFactory;
use Drewlabs\Overloadable\Overloadable;
use Drewlabs\Query\AggregationMethods;
use Drewlabs\Query\Contracts\EnumerableResultInterface;
use Drewlabs\Query\Contracts\FiltersInterface;
use Drewlabs\Query\Contracts\Queryable;
use Drewlabs\Query\Contracts\QueryLanguageInterface;
use Drewlabs\Query\Contracts\TransactionManagerInterface;
use Drewlabs\Support\Traits\MethodProxy;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @method Queryable|mixed                 create(array $attributes, \Closure $callback = null)
 * @method Queryable|mixed                 create(array $attributes, $params, bool $batch, \Closure $callback = null)
 * @method Queryable|mixed                 create(array $attributes, $params = [], \Closure $callback)
 * @method bool                            delete(int $id)
 * @method bool                            delete(string $id)
 * @method int                             delete(array $query)
 * @method int                             delete(array $query, bool $batch)
 * @method EnumerableResultInterface|mixed select()
 * @method Queryable|mixed                 select(string $id, array $columns, \Closure $callback = null)
 * @method Queryable|mixed                 select(string $id, \Closure $callback = null)
 * @method Queryable|mixed                 select(int $id, array $columns, \Closure $callback = null)
 * @method Queryable|mixed                 select(int $id, \Closure $callback = null)
 * @method EnumerableResultInterface|mixed select(array $query, \Closure $callback = null)
 * @method EnumerableResultInterface|mixed select(array $query, array $columns, \Closure $callback = null)
 * @method mixed                           select(array $query, int $per_page, int $page = null, \Closure $callback = null)
 * @method mixed                           select(array $query, int $per_page, array $columns, int $page = null, \Closure $callback = null)
 * @method int                             selectAggregate(array $query = [], string $aggregation = \Drewlabs\Laravel\Query\AggregationMethods::COUNT)
 * @method int                             update(array $query, $attributes = [])
 * @method int                             update(array $query, $attributes = [], bool $bulkstatement)
 * @method Queryable|mixed                 update(int $id, $attributes, \Closure $dto_transform_fn = null)
 * @method Queryable|mixed                 update(int $id, $attributes, $params, \Closure $dto_transform_fn = null)
 * @method Queryable|mixed                 update(string $id, $attributes, \Closure $dto_transform_fn = null)
 * @method Queryable|mixed                 update(string $id, $attributes, $params, \Closure $dto_transform_fn = null)
 */
final class QueryLanguage implements QueryLanguageInterface, ProvidesFiltersFactory
{
    use CreateQueryLanguage;
    use DeleteQueryLanguage;
    use MethodProxy;
    use Overloadable;
    use SelectQueryLanguage;
    use UpdateQueryLanguage;

    public const AGGREGATE_METHODS = [
        AggregationMethods::COUNT,
        AggregationMethods::AVERAGE,
        AggregationMethods::MAX,
        AggregationMethods::MIN,
        AggregationMethods::SUM,
    ];

    /**
     * @var string
     */
    private $blueprint;

    /**
     * @var Queryable|Model
     */
    private $queryable;

    /**
     * @var \Closure(mixed, array|FiltersInterface): mixed
     */
    private $builderFactory;

    /**
     * @var TransactionManagerInterface
     */
    private $transactions;

    /**
     * @var \Closure(mixed|array): QueryFilters
     */
    private $filtersFactory;

    /**
     * Creates class instance.
     *
     * @param Queryable|class-string<Queryable> $queryable
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($queryable)
    {
        // #region Set required properties value
        $this->setQueryable($queryable);
        $this->setTransactionManager($this->useDefaultTransactionManager());
        $this->setBuilderFactory($this->defaultBuilderFactory());
        $this->setFiltersFactory($this->useDefaultQueryFactory());
        // #endregion Set required properties value
    }

    /**
     * Creates Query Language instance.
     *
     * @param mixed $blueprint
     *
     * @return static
     */
    public static function new($blueprint)
    {
        return new static($blueprint);
    }

    public function createMany(array $attributes)
    {
        if (!(array_filter($attributes, 'is_array') === $attributes)) {
            throw new \InvalidArgumentException(__METHOD__.' requires an list of list items for insertion');
        }

        return $this->queryable->insert(array_map(function ($value) {
            return array_merge($this->parseAttributes($value), ['updated_at' => date('Y-m-d H:i:s'), 'created_at' => date('Y-m-d H:i:s')]);
        }, $attributes));
    }

    /**
     * Provides an aggregation interface definition.
     *
     * @param mixed $args
     *
     * @throws \InvalidArgumentException
     * @throws \Error
     * @throws \BadMethodCallException
     *
     * @return int|mixed
     */
    public function aggregate(array $query = [], string $aggregation = AggregationMethods::COUNT, ...$args)
    {
        if (!\in_array($aggregation, static::AGGREGATE_METHODS, true)) {
            throw new \InvalidArgumentException('The provided method is not part of the aggregation framework supported methods');
        }

        return $this->proxy($this->builderFactory()($this->queryable, $query), $aggregation, [...$args]);
    }

    /**
     * Provides an aggregation interface implementation.
     *
     * @param mixed $args
     *
     * @throws \InvalidArgumentException
     * @throws \Error
     * @throws \BadMethodCallException
     *
     * @return int|mixed
     */
    public function selectAggregate(array $query = [], string $aggregation = AggregationMethods::COUNT, ...$args)
    {
        return $this->aggregate($query, $aggregation, ...$args);
    }

    public function getQueryable()
    {
        return $this->queryable;
    }

    /**
     * {@inheritDoc}
     *
     * @return self
     */
    public function setFiltersFactory(\Closure $factory)
    {
        $this->filtersFactory = $factory;

        return $this;
    }

    /**
     * Return the filters factory instance.
     *
     * @return Closure(mixed|array $queries): FiltersInterface
     */
    public function getFiltersFactory()
    {
        return $this->filtersFactory;
    }

    /**
     * Query language builder factory getter.
     *
     * @return Closure(mixed $builder, array|FiltersInterface $query): Builder
     */
    public function builderFactory()
    {
        return $this->builderFactory;
    }

    /**
     * Query Language builder factory setter method.
     *
     * @return self
     */
    public function setBuilderFactory(\Closure $factory)
    {
        $this->builderFactory = $factory;

        return $this;
    }

    /**
     * Set the transaction manager instance for the current object.
     *
     * @return self
     */
    public function setTransactionManager(TransactionManagerInterface $transactions)
    {
        $this->transactions = $transactions;

        return $this;
    }

    /**
     * Set the queryable instance on this object.
     *
     * @param mixed $queryable
     *
     * @throws \InvalidArgumentException
     *
     * @return $this
     */
    public function setQueryable($queryable)
    {
        [$instance, $blueprint] = \is_string($queryable) ? [new $queryable(), $queryable] : [$queryable, $queryable::class];
        // Check for the type of queryable instance
        if (!($instance instanceof Queryable)) {
            throw new \InvalidArgumentException('constructor requires an instance of '.Queryable::class.', or a Queryable class string');
        }
        $this->queryable = $instance;
        $this->blueprint = $blueprint;

        // Return the current instance
        return $this;
    }

    /**
     * Provides a default builder factory that is used to invoke database queries.
     *
     * @return Closure(mixed $builder, array|FiltersInterface $query): mixed
     */
    private function defaultBuilderFactory()
    {
        /*
         * @param Builder                $builder
         * @param array|FiltersInterface $query
         */
        return function ($builder, $query = []) {
            return \is_array($query) ? array_reduce((array_filter($query, 'is_array') === $query) && !(array_keys($query) !== range(0, \count($query) - 1)) ? $query : [$query], function ($builder, $query) {
                return $this->getFiltersFactory()($query)->call($builder);
            }, $builder) : (null === $query ? $builder : $query->call($builder));
        };
    }

    /**
     * Creates attributes array from mixed type.
     *
     * @param array|object $attributes
     *
     * @return array
     */
    private function attributesToArray($attributes)
    {
        if (\is_array($attributes)) {
            return $attributes;
        }

        return Arr::create($attributes);
    }

    /**
     * Prepares database table attributes.
     *
     * @return array
     */
    private function parseAttributes(array $attributes)
    {
        // Get the value of the model fillable property
        $fillable = $this->queryable->getFillable() ?? [];
        // We assume that if developper do not provide fillable properties
        // the input from request should be passed to
        if (empty($fillable)) {
            return $attributes;
        }

        return iterator_to_array($this->filterQueryableAttributes($fillable, $attributes));
    }

    /**
     * Produces an array of properties that are supported by the queryable instance.
     *
     * @return \Traversable<mixed, mixed, mixed, void>
     */
    private function filterQueryableAttributes(array $properties, array $attributes)
    {
        foreach ($properties as $value) {
            if (\array_key_exists($value, $attributes)) {
                yield $value => $attributes[$value];
            }
        }
    }

    /**
     * Use the eloquent query filter as default query filter.
     *
     * @return Closure(mixed|array $queries): QueryFilters
     */
    private function useDefaultQueryFactory()
    {
        return static function (array $queries) {
            return new QueryFilters($queries);
        };
    }

    /**
     * Set the instance to use the default transaction manager.
     *
     * @return TransactionManager
     */
    private function useDefaultTransactionManager()
    {
        return new TransactionManager(new TransactionClient($this->queryable->getConnection()));
    }
}
