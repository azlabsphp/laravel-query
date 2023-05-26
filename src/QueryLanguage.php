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

use BadMethodCallException;
use Closure;
use Drewlabs\Query\Contracts\FiltersInterface;
use Drewlabs\Core\Helpers\Arr;
use Drewlabs\Overloadable\Overloadable;
use Drewlabs\Packages\Database\TransactionClient;

use Drewlabs\Packages\Database\Query\Concerns\CreateQueryLanguage;
use Drewlabs\Packages\Database\Query\Concerns\DeleteQueryLanguage;
use Drewlabs\Packages\Database\Query\Concerns\SelectQueryLanguage;
use Drewlabs\Packages\Database\Query\Concerns\UpdateQueryLanguage;
use Drewlabs\Packages\Database\Traits\ProvidesBuilderFactory;
use Drewlabs\Query\AggregationMethods;
use Drewlabs\Query\Contracts\QueryLanguageInterface;
use Drewlabs\Support\Traits\MethodProxy;
use InvalidArgumentException;
use Error;
use Drewlabs\Query\Contracts\Queryable;
use Illuminate\Database\Eloquent\Model;
use Drewlabs\Query\Contracts\EnumerableResultInterface;

/**
 * @method Queryable|mixed                                      create(array $attributes, \Closure $callback = null)
 * @method Queryable|mixed                                      create(array $attributes, $params, bool $batch, \Closure $callback = null)
 * @method Queryable|mixed                                      create(array $attributes, $params = [], \Closure $callback)
 * @method bool                                                 delete(int $id)
 * @method bool                                                 delete(string $id)
 * @method int                                                  delete(array $query)
 * @method int                                                  delete(array $query, bool $batch)
 * @method EnumerableResultInterface|mixed                      select()
 * @method Queryable|mixed                                      select(string $id, array $columns, \Closure $callback = null)
 * @method Queryable|mixed                                      select(string $id, \Closure $callback = null)
 * @method Queryable|mixed                                      select(int $id, array $columns, \Closure $callback = null)
 * @method Queryable|mixed                                      select(int $id, \Closure $callback = null)
 * @method EnumerableResultInterface|mixed                      select(array $query, \Closure $callback = null)
 * @method EnumerableResultInterface|mixed                      select(array $query, array $columns, \Closure $callback = null)
 * @method mixed                                                select(array $query, int $per_page, int $page = null, \Closure $callback = null)
 * @method mixed                                                select(array $query, int $per_page, array $columns, int $page = null, \Closure $callback = null)
 * @method int                                                  selectAggregate(array $query = [], string $aggregation = \Drewlabs\Packages\Database\AggregationMethods::COUNT)
 * @method int                                                  update(array $query, $attributes = [])
 * @method int                                                  update(array $query, $attributes = [], bool $bulkstatement)
 * @method Queryable|mixed                                      update(int $id, $attributes, \Closure $dto_transform_fn = null)
 * @method Queryable|mixed                                      update(int $id, $attributes, $params, \Closure $dto_transform_fn = null)
 * @method Queryable|mixed                                      update(string $id, $attributes, \Closure $dto_transform_fn = null)
 * @method Queryable|mixed                                      update(string $id, $attributes, $params, \Closure $dto_transform_fn = null)
 */
final class QueryLanguage implements QueryLanguageInterface
{
    use CreateQueryLanguage;
    use DeleteQueryLanguage;
    use MethodProxy;
    use Overloadable;
    use SelectQueryLanguage;
    use UpdateQueryLanguage;
    use ProvidesBuilderFactory;

    public const AGGREGATE_METHODS = [
        AggregationMethods::COUNT,
        AggregationMethods::AVERAGE,
        AggregationMethods::MAX,
        AggregationMethods::MIN,
        AggregationMethods::SUM,
    ];

    /**
     *
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
     * Creates class instance
     * 
     * @param mixed $blueprint 
     * @return void 
     * @throws InvalidArgumentException 
     */
    public function __construct($blueprint)
    {
        if (!(\is_string($blueprint) || ($blueprint instanceof Model))) {
            throw new \InvalidArgumentException('Constructor requires an instance of ' . Model::class . ', or a Model class name');
        }
        $this->queryable = \is_string($blueprint) ? new $blueprint() : $blueprint;
        $this->blueprint = \is_string($blueprint) ? $blueprint : \get_class($blueprint);
        $this->transactions = new TransactionManager(new TransactionClient($this->queryable->getConnection()));
        $this->setBuilderFactory($this->defaultBuilderFactory());
    }

    /**
     * Creates Query Language instance
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
            throw new \InvalidArgumentException(__METHOD__ . ' requires an list of list items for insertion');
        }
        return $this->queryable->insert(array_map(function ($value) {
            return array_merge($this->parseAttributes($value), ['updated_at' => date('Y-m-d H:i:s'), 'created_at' => date('Y-m-d H:i:s')]);
        }, $attributes));
    }

    /**
     * Provides an aggregation interface definition
     * 
     * @param array $query 
     * @param string $aggregation 
     * @param mixed $args 
     * @return int|mixed 
     * @throws InvalidArgumentException 
     * @throws Error 
     * @throws BadMethodCallException 
     */
    public function aggregate(array $query = [], string $aggregation = AggregationMethods::COUNT, ...$args)
    {
        if (!\in_array($aggregation, static::AGGREGATE_METHODS, true)) {
            throw new \InvalidArgumentException('The provided method is not part of the aggregation framework supported methods');
        }
        return $this->proxy($this->builderFactory()($this->queryable, $query), $aggregation, [...$args]);
    }

    /**
     * Provides an aggregation interface implementation
     * 
     * @param array $query 
     * @param string $aggregation 
     * @param mixed $args 
     * @return int|mixed 
     * @throws InvalidArgumentException 
     * @throws Error 
     * @throws BadMethodCallException 
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
     * Provides a default builder factory that is used to invoke database queries.
     *
     * @return Closure(mixed $builder, array|FiltersInterface $query): mixed
     */
    private function defaultBuilderFactory()
    {
        return static function ($builder, $query) {
            return \is_array($query) ? array_reduce((array_filter($query, 'is_array') === $query) && !(array_keys($query) !== range(0, \count($query) - 1)) ? $query : [$query], static function ($builder, $query) {
                return (new EloquentQueryFilters($query))->apply($builder);
            }, $builder) : $query->apply($builder);
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
     * Produces an array of properties that are supported by the queryable instance
     * 
     * @param array $properties 
     * @param array $attributes 
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
}
