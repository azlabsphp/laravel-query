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

use Closure;
use Drewlabs\Contracts\Data\DML\DMLProvider;
use Drewlabs\Contracts\Data\Filters\FiltersInterface;
use Drewlabs\Contracts\Data\Model\ActiveModel;
use Drewlabs\Contracts\Data\Model\Model;
use Drewlabs\Core\Helpers\Arr;
use Drewlabs\Packages\Database\Contracts\QueryLanguageInterface;
use Drewlabs\Packages\Database\Contracts\TransactionManagerInterface;
use Drewlabs\Packages\Database\Eloquent\QueryMethod;
use function Drewlabs\Packages\Database\Proxy\ModelFiltersHandler;

use Drewlabs\Packages\Database\Query\Concerns\CreateQueryLanguage;
use Drewlabs\Packages\Database\Query\Concerns\DeleteQueryLanguage;
use Drewlabs\Packages\Database\Query\Concerns\SelectQueryLanguage;
use Drewlabs\Packages\Database\Query\Concerns\UpdateQueryLanguage;
use Drewlabs\Packages\Database\Traits\ContainerAware;
use Drewlabs\Support\Traits\MethodProxy;
use Drewlabs\Support\Traits\Overloadable;

/**
 * @method \Drewlabs\Contracts\Data\Model\Model|mixed           create(array $attributes, \Closure $callback = null)
 * @method \Drewlabs\Contracts\Data\Model\Model|mixed           create(array $attributes, $params, bool $batch, \Closure $callback = null)
 * @method \Drewlabs\Contracts\Data\Model\Model|mixed           create(array $attributes, $params = [], \Closure $callback)
 * @method bool                                                 delete(int $id)
 * @method bool                                                 delete(string $id)
 * @method int                                                  delete(array $query)
 * @method int                                                  delete(array $query, bool $batch)
 * @method \Drewlabs\Contracts\Data\EnumerableQueryResult|mixed select()
 * @method \Drewlabs\Contracts\Data\Model\Model|mixed           select(string $id, array $columns, \Closure $callback = null)
 * @method \Drewlabs\Contracts\Data\Model\Model|mixed           select(string $id, \Closure $callback = null)
 * @method \Drewlabs\Contracts\Data\Model\Model|mixed           select(int $id, array $columns, \Closure $callback = null)
 * @method \Drewlabs\Contracts\Data\Model\Model|mixed           select(int $id, \Closure $callback = null)
 * @method \Drewlabs\Contracts\Data\EnumerableQueryResult|mixed select(array $query, \Closure $callback = null)
 * @method \Drewlabs\Contracts\Data\EnumerableQueryResult|mixed select(array $query, array $columns, \Closure $callback = null)
 * @method mixed                                                select(array $query, int $per_page, int $page = null, \Closure $callback = null)
 * @method mixed                                                select(array $query, int $per_page, array $columns, int $page = null, \Closure $callback = null)
 * @method int                                                  selectAggregate(array $query = [], string $aggregation = \Drewlabs\Packages\Database\AggregationMethods::COUNT)
 * @method int                                                  update(array $query, $attributes = [])
 * @method int                                                  update(array $query, $attributes = [], bool $bulkstatement)
 * @method \Drewlabs\Contracts\Data\Model\Model|mixed           update(int $id, $attributes, \Closure $dto_transform_fn = null)
 * @method \Drewlabs\Contracts\Data\Model\Model|mixed           update(int $id, $attributes, $params, \Closure $dto_transform_fn = null)
 * @method \Drewlabs\Contracts\Data\Model\Model|mixed           update(string $id, $attributes, \Closure $dto_transform_fn = null)
 * @method \Drewlabs\Contracts\Data\Model\Model|mixed           update(string $id, $attributes, $params, \Closure $dto_transform_fn = null)
 */
final class QueryLanguage implements DMLProvider, QueryLanguageInterface
{
    use ContainerAware;
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
     * The Eloquent model class binded to the current DML provider.
     *
     * @var string
     */
    private $model_class;

    /**
     * @var Model|ActiveModel|mixed
     */
    private $model;

    /**
     * @var \Closure(mixed, array|FiltersInterface): mixed
     */
    private $builderFactory;

    /**
     * @var TransactionManagerInterface
     */
    private $transactionManager;

    /**
     * @param Model|string $blueprint
     *
     * @throws \Exception
     * @throws \InvalidArgumentException
     */
    public function __construct($blueprint)
    {
        if (!(\is_string($blueprint) || ($blueprint instanceof Model))) {
            throw new \InvalidArgumentException('Constructor requires an instance of '.Model::class.', or a Model class name');
        }
        $this->model = \is_string($blueprint) ? self::createResolver($blueprint)() : $blueprint;
        $this->model_class = \is_string($blueprint) ? $blueprint : \get_class($blueprint);
        $this->transactionManager = TransactionManager::new($this->model);
        $this->setBuilderFactory($this->defaultBuilderFactory());
    }

    public static function for($clazz)
    {
        return new static($clazz);
    }

    public function createMany(array $attributes)
    {
        if (!(array_filter($attributes, 'is_array') === $attributes)) {
            throw new \InvalidArgumentException(__METHOD__.' requires an list of list items for insertion');
        }

        return $this->proxy(
            drewlabs_core_create_attribute_getter('model', null)($this),
            QueryMethod::INSERT_MANY,
            [
                array_map(function ($value) {
                    return array_merge($this->parseAttributes($value), ['updated_at' => date('Y-m-d H:i:s'), 'created_at' => date('Y-m-d H:i:s')]);
                }, $attributes),
            ],
        );
    }

    public function aggregate(array $query = [], string $aggregation = AggregationMethods::COUNT)
    {
        if (!\in_array($aggregation, static::AGGREGATE_METHODS, true)) {
            throw new \InvalidArgumentException('The provided method is not part of the aggregation framework supported methods');
        }
        $model = drewlabs_core_create_attribute_getter('model', null)($this);

        return $this->proxy($this->builderFactory()($model, $query), $aggregation, []);
    }

    public function selectAggregate(array $query = [], string $aggregation = AggregationMethods::COUNT)
    {
        return $this->aggregate($query, $aggregation);
    }

    /**
     * Query language builder factory getter.
     *
     * @return Closure(mixed $builder, array|FiltersInterface $query): mixed
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
     * Provides a default builder factory that is used to invoke database queries.
     *
     * @return Closure(mixed $builder, array|FiltersInterface $query): mixed
     */
    private function defaultBuilderFactory()
    {
        return static function ($builder, $query) {
            return \is_array($query) ? array_reduce(Arr::isnotassoclist($query) ? $query : [$query], static function ($builder, $query) {
                return ModelFiltersHandler($query)->apply($builder);
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
        $model = drewlabs_core_create_attribute_getter('model', null)($this);

        // For models that does not defines `getFillable` method, we simply
        // return the `attributes` parameter as we do not have any property
        // that might helps in parsing input attributes
        // Such models are not protected against mass assignement relates errors
        if (!(method_exists($model, 'getFillable'))) {
            return $attributes;
        }
        // Get the value of the model fillable property
        $fillable = $model->getFillable() ?? [];
        // We assume that if developper do not provide fillable properties
        // the input from request should be passed to
        if (empty($fillable)) {
            return $attributes;
        }

        return Arr::create($this->yieldAttributes($fillable, $attributes));
    }

    private function yieldAttributes(array $properties, array $attributes)
    {
        foreach ($properties as $value) {
            if (\array_key_exists($value, $attributes)) {
                yield $value => $attributes[$value];
            }
        }
    }
}
