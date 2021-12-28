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

use Drewlabs\Contracts\Data\DML\DMLProvider;
use Drewlabs\Contracts\Data\Model\ActiveModel;
use Drewlabs\Contracts\Data\Model\Model;
use Drewlabs\Packages\Database\Extensions\CustomQueryCriteria;
use Drewlabs\Packages\Database\Extensions\IlluminateModelRepository;
use Drewlabs\Packages\Database\Traits\AttributesParser;
use Drewlabs\Packages\Database\Traits\DMLCreateQuery;
use Drewlabs\Packages\Database\Traits\DMLDeleteQuery;
use Drewlabs\Packages\Database\Traits\DMLSelectQuery;
use Drewlabs\Packages\Database\Traits\DMLUpdateQuery;
use Drewlabs\Support\Traits\Overloadable;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Traits\ForwardsCalls;

/**
 * @method \Drewlabs\Contracts\Data\Model\Model|mixed                      create(array $attributes, \Closure $callback = null)
 * @method \Drewlabs\Contracts\Data\Model\Model|mixed                      create(array $attributes, $params, bool $batch, \Closure $callback = null)
 * @method \Drewlabs\Contracts\Data\Model\Model|mixed                      create(array $attributes, $params = [], \Closure $callback)
 * @method bool                                                            delete(int $id)
 * @method bool                                                            delete(string $id)
 * @method int                                                             delete(array $query)
 * @method int                                                             delete(array $query, bool $batch)
 * @method \Drewlabs\Contracts\Data\DataProviderQueryResultInterface|mixed select()
 * @method \Drewlabs\Contracts\Data\Model\Model|mixed                      select(string $id, array $columns, \Closure $callback = null)
 * @method \Drewlabs\Contracts\Data\Model\Model|mixed                      select(string $id, \Closure $callback = null)
 * @method \Drewlabs\Contracts\Data\Model\Model|mixed                      select(int $id, array $columns, \Closure $callback = null)
 * @method \Drewlabs\Contracts\Data\Model\Model|mixed                      select(int $id, \Closure $callback = null)
 * @method \Drewlabs\Contracts\Data\DataProviderQueryResultInterface|mixed select(array $query, \Closure $callback = null)
 * @method \Drewlabs\Contracts\Data\DataProviderQueryResultInterface|mixed select(array $query, array $columns, \Closure $callback = null)
 * @method \Illuminate\Contracts\Pagination\Paginator|mixed                select(array $query, int $per_page, int $page = null, \Closure $callback = null)
 * @method \Illuminate\Contracts\Pagination\Paginator|mixed                select(array $query, int $per_page, array $columns, int $page = null, \Closure $callback = null)
 * @method int                                                             selectAggregate(array $query = [], string $aggregation = \Drewlabs\Packages\Database\DatabaseQueryBuilderAggregationMethodsEnum::COUNT)
 * @method int                                                             update(array $query, $attributes = [])
 * @method int                                                             update(array $query, $attributes = [], bool $bulkstatement)
 * @method \Drewlabs\Contracts\Data\Model\Model|mixed                      update(int $id, $attributes, \Closure $dto_transform_fn = null)
 * @method \Drewlabs\Contracts\Data\Model\Model|mixed                      update(int $id, $attributes, $params, \Closure $dto_transform_fn = null)
 * @method \Drewlabs\Contracts\Data\Model\Model|mixed                      update(string $id, $attributes, \Closure $dto_transform_fn = null)
 * @method \Drewlabs\Contracts\Data\Model\Model|mixed                      update(string $id, $attributes, $params, \Closure $dto_transform_fn = null)
 */
class EloquentDMLManager implements DMLProvider
{
    use AttributesParser;
    use DMLCreateQuery;
    use DMLDeleteQuery;
    use DMLSelectQuery;
    use DMLUpdateQuery;
    use ForwardsCalls;
    use Overloadable;

    public const AGGREGATE_METHODS = [
        DatabaseQueryBuilderAggregationMethodsEnum::COUNT,
        DatabaseQueryBuilderAggregationMethodsEnum::AVERAGE,
        DatabaseQueryBuilderAggregationMethodsEnum::MAX,
        DatabaseQueryBuilderAggregationMethodsEnum::MIN,
        DatabaseQueryBuilderAggregationMethodsEnum::SUM,
    ];

    /**
     * The Eloquent model class binded to the current DML provider.
     *
     * @var string
     */
    private $model_class;

    /**
     * @var Model|ActiveModel|Eloquent
     */
    private $model;

    /**
     * @param Model|string $clazz
     *
     * @throws BindingResolutionException
     * @throws \InvalidArgumentException
     *
     * @return never
     */
    public function __construct($clazz)
    {
        if (!(\is_string($clazz) || ($clazz instanceof Model))) {
            throw new \InvalidArgumentException(
                'Constructor requires an instance of '.Model::class.', or a Model class name'
            );
        }
        if (\is_string($clazz)) {
            $this->model_class = $clazz;
            // Create the model instance
            $this->model = Container::getInstance()->make($this->model_class);

            return;
        }

        if ($clazz instanceof Model) {
            $this->model = $clazz;
            $this->model_class = \get_class($clazz);
        }
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

        return $this->forwardCallTo(
            drewlabs_core_create_attribute_getter('model', null)($this),
            EloquentQueryBuilderMethodsEnum::INSERT_MANY,
            [
                array_map(function ($value) {
                    return array_merge(
                        $this->parseAttributes($value),
                        [
                            'updated_at' => date('Y-m-d H:i:s'),
                            'created_at' => date('Y-m-d H:i:s'),
                        ]
                    );
                }, $attributes),
            ],
        );
    }

    /**
     * Run an aggregation method on a query builder result.
     *
     * @return int|mixed
     */
    public function selectAggregate(array $query = [], string $aggregation = DatabaseQueryBuilderAggregationMethodsEnum::COUNT)
    {
        if (!\in_array($aggregation, static::AGGREGATE_METHODS, true)) {
            throw new \InvalidArgumentException('The provided method is not part of the aggregation framework supported methods');
        }

        return $this->forwardCallTo(
            array_reduce(drewlabs_core_array_is_no_assoc_array_list($query) ? $query : [$query], static function ($model, $q) {
                return (new CustomQueryCriteria($q))->apply($model);
            }, drewlabs_core_create_attribute_getter('model', null)($this)),
            $aggregation,
            []
        );
    }

    /**
     * {@inheritDoc}
     */
    public function createRepository()
    {
        return new IlluminateModelRepository($this->model_class);
    }
}
