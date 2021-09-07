<?php

namespace Drewlabs\Packages\Database;

use Drewlabs\Contracts\Data\DataProviderHandlerParamsInterface;
use Drewlabs\Contracts\Data\DML\DMLProvider;
use Drewlabs\Contracts\Data\Model\ActiveModel;
use Drewlabs\Contracts\Data\Model\Model;
use Drewlabs\Core\Data\Services\ModelAttributesParser;
use Drewlabs\Support\Traits\Overloadable;
use Drewlabs\Packages\Database\Extensions\CustomQueryCriteria;
use Drewlabs\Packages\Database\Extensions\IlluminateModelRepository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Traits\ForwardsCalls;
use InvalidArgumentException;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Drewlabs\Contracts\Data\DataProviderQueryResultInterface;
use Drewlabs\Core\Data\DataProviderQueryResult;
use Drewlabs\Packages\Database\Helpers\SelectQueryColumnsHelper;
use Drewlabs\Support\Collections\SimpleCollection;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Enumerable;

use function Drewlabs\Packages\Database\Proxy\SelectQueryResult;

/**
 * @method \Drewlabs\Contracts\Data\Model\Model|mixed create(array $attributes, \Closure $callback = null)
 * @method \Drewlabs\Contracts\Data\Model\Model|mixed create(array $attributes, $params, bool $batch, \Closure $callback = null)
 * @method \Drewlabs\Contracts\Data\Model\Model|mixed create(array $attributes, $params = [], \Closure $callback)
 * 
 * @method bool delete(int $id)
 * @method bool delete(string $id)
 * @method int delete(array $query)
 * @method int delete(array $query, bool $batch)
 * 
 * @method \Drewlabs\Contracts\Data\DataProviderQueryResultInterface|mixed select()
 * @method \Drewlabs\Contracts\Data\Model\Model|mixed select(string $id, array $columns, \Closure $callback = null)
 * @method \Drewlabs\Contracts\Data\Model\Model|mixed select(string $id, \Closure $callback = null)
 * @method \Drewlabs\Contracts\Data\Model\Model|mixed select(int $id, array $columns, \Closure $callback = null)
 * @method \Drewlabs\Contracts\Data\Model\Model|mixed select(int $id, \Closure $callback = null)
 * @method \Drewlabs\Contracts\Data\DataProviderQueryResultInterface|mixed select(array $query, \Closure $callback = null)
 * @method \Drewlabs\Contracts\Data\DataProviderQueryResultInterface|mixed select(array $query, array $columns, \Closure $callback = null)
 * @method \Illuminate\Contracts\Pagination\Paginator|mixed select(array $query, int $per_page, int $page = null, \Closure $callback = null)
 * @method \Illuminate\Contracts\Pagination\Paginator|mixed select(array $query, int $per_page, array $columns, int $page = null, \Closure $callback = null)
 * @method int selectAggregate(array $query = [], string $aggregation = \Drewlabs\Packages\Database\DatabaseQueryBuilderAggregationMethodsEnum::COUNT)
 * 
 * @method int update(array $query, $attributes = [])
 * @method int update(array $query, $attributes = [], bool $bulkstatement)
 * @method \Drewlabs\Contracts\Data\Model\Model|mixed update(int $id, $attributes, \Closure $dto_transform_fn = null)
 * @method \Drewlabs\Contracts\Data\Model\Model|mixed update(int $id, $attributes, $params, \Closure $dto_transform_fn = null)
 * @method \Drewlabs\Contracts\Data\Model\Model|mixed update(string $id, $attributes, \Closure $dto_transform_fn = null)
 * @method \Drewlabs\Contracts\Data\Model\Model|mixed update(string $id, $attributes, $params, \Closure $dto_transform_fn = null)
 */
class EloquentDMLManager implements DMLProvider
{

    use ForwardsCalls;
    use Overloadable;

    /**
     * The Eloquent model class binded to the current DML provider
     *
     * @var string
     */
    private $model_class;

    /**
     *
     * @var Model|ActiveModel|Eloquent
     */
    private $model;

    const AGGREGATE_METHODS = [
        DatabaseQueryBuilderAggregationMethodsEnum::COUNT,
        DatabaseQueryBuilderAggregationMethodsEnum::AVERAGE,
        DatabaseQueryBuilderAggregationMethodsEnum::MAX,
        DatabaseQueryBuilderAggregationMethodsEnum::MIN,
        DatabaseQueryBuilderAggregationMethodsEnum::SUM
    ];

    /**
     * 
     * @param Model|string $clazz 
     * @return never 
     * @throws BindingResolutionException 
     * @throws InvalidArgumentException 
     */
    public function __construct($clazz)
    {
        if (!(is_string($clazz) || ($clazz instanceof Model))) {
            throw new InvalidArgumentException("Constructor requires an instance of " . Model::class . ", or a Model class name");
        }
        if (is_string($clazz)) {
            $this->model_class = $clazz;
            // Create the model instance
            $this->model = Container::getInstance()->make($this->model_class);
            return;
        }

        if ($clazz instanceof Model) {
            $this->model = $clazz;
            $this->model_class = get_class($clazz);
        }
    }

    public static function for($clazz)
    {
        return new static($clazz);
    }

    /**
     * @inheritDoc
     */
    public function create(...$args)
    {
        return $this->model->getConnection()->transaction(function () use ($args) {
            return $this->overload($args, [
                'createV1',
                'createV2',
                'createV3'
            ]);
        });
    }

    /**
     * @param array $attributes 
     * @param \Closure|null $callback
     * @return Model 
     */
    public function createV1(array $attributes, \Closure $callback = null)
    {
        $callback = $callback ? $callback : function ($param) {
            return $param;
        };
        return $callback(
            $this->forwardCallTo(
                drewlabs_core_create_attribute_getter('model', null)($this),
                'add',
                [$this->parseAttributes($attributes)]
            )
        );
    }

    /**
     *
     * @param array $attributes
     * @param array|DataProviderHandlerParamsInterface $params
     * @param \Closure|null $callback
     * @return Model
     */
    public function createV2(array $attributes, $params, \Closure $callback = null)
    {
        if (!(is_array($params) || ($params instanceof DataProviderHandlerParamsInterface))) {
            throw new InvalidArgumentException('Argument 2 of the create method must be an array or an instance of ' . DataProviderHandlerParamsInterface::class);
        }
        return $this->handleCreateStatement(
            $attributes,
            drewlabs_database_parse_create_handler_params($params),
            false,
            $callback
        );
    }

    /**
     *
     * @param array $attributes
     * @param array|DataProviderHandlerParamsInterface $params
     * @param bool $batch
     * @param \Closure|null $callback
     * @return Model
     */
    public function createV3(array $attributes, $params, bool $batch, \Closure $callback = null)
    {
        if (!(is_array($params) || ($params instanceof DataProviderHandlerParamsInterface))) {
            throw new InvalidArgumentException('Argument 2 of the create method must be an array or an instance of ' . DataProviderHandlerParamsInterface::class);
        }
        return $this->handleCreateStatement(
            $attributes,
            drewlabs_database_parse_create_handler_params($params),
            $batch,
            $callback
        );
    }

    private function handleCreateStatement(array $attributes, array $params, bool $batch = false, \Closure $callback = null)
    {
        $callback = $callback ? $callback : function ($param) {
            return $param;
        };
        $method = $params['method'] ?? EloquentQueryBuilderMethodsEnum::CREATE;
        $upsert = $params['upsert'] ?? false;
        $upsert_conditions = $params['upsert_conditions'] ? $params['upsert_conditions'] : [];
        if (is_string($method) && \drewlabs_database_is_dynamic_create_method($method)) {
            $result = create_relations_after_create(
                $this->forwardCallTo(
                    drewlabs_core_create_attribute_getter('model', null)($this),
                    $upsert ? EloquentQueryBuilderMethodsEnum::UPSERT : EloquentQueryBuilderMethodsEnum::CREATE,
                    // if Upserting, pass the upsertion condition first else, pass in the attributes
                    $upsert ? [$upsert_conditions, $attributes] : [$this->parseAttributes($attributes)]
                ),
                array_slice(drewlabs_database_parse_dynamic_callback($method), 1),
                $attributes,
                $batch
            );
        } else {
            $result = $this->forwardCallTo(
                drewlabs_core_create_attribute_getter('model', null)($this),
                $method,
                // if Upserting, pass the upsertion condition first else, pass in the attributes
                $upsert ? [$upsert_conditions, $attributes] : [$this->parseAttributes($attributes)]
            );
        }
        return $callback($result);
    }

    public function createMany(array $attributes)
    {
        if (!(\array_filter($attributes, 'is_array') === $attributes)) {
            throw new InvalidArgumentException(__METHOD__ . ' requires an list of list items for insertion');
        }
        return $this->forwardCallTo(
            drewlabs_core_create_attribute_getter('model', null)($this),
            EloquentQueryBuilderMethodsEnum::INSERT_MANY,
            [
                array_map(function ($value) {
                    return array_merge(
                        $this->parseAttributes($value),
                        array(
                            'updated_at' => date('Y-m-d H:i:s'),
                            'created_at' => date('Y-m-d H:i:s')
                        )
                    );
                }, $attributes)
            ],
        );
    }

    public function delete(...$args)
    {
        return $this->model->getConnection()->transaction(function () use ($args) {
            return $this->overload($args, [
                'deleteV1',
                'deleteV2',
                'deleteV3',
                'deleteV4'
            ]);
        });
    }

    /**
     *
     * @param integer $id
     * @return bool
     */
    public function deleteV1(int $id)
    {
        return $this->deleteV2((string)$id);
    }

    /**
     *
     * @param string $id
     * @return bool
     */
    public function deleteV2(string $id)
    {
        $count = $this->deleteV3([
            'where' => [$this->model->getPrimaryKey(), $id]
        ]);
        return (int)$count === 1 ? true : false;
    }

    /**
     *
     * @param array $query
     * @return int
     */
    public function deleteV3(array $query)
    {
        return $this->deleteV4($query, false);
    }

    /**
     *
     * @param array $query
     * @param boolean $batch
     * @return int
     */
    public function deleteV4(array $query, bool $batch)
    {
        if ($batch) {
            return $this->forwardCallTo(
                array_reduce(drewlabs_core_array_is_no_assoc_array_list($query) ? $query : [$query], function ($model, $q) {
                    return (new CustomQueryCriteria($q))->apply($model);
                }, drewlabs_core_create_attribute_getter('model', null)($this)),
                EloquentQueryBuilderMethodsEnum::DELETE,
                []
            );
        } else {
            // Select the matching columns
            $collection = $this->selectV3($query, function ($result) {
                return $result->getCollection();
            });
            // Loop through the matching columns and update each
            return array_reduce(
                is_array($collection) ?
                    $collection : ($collection instanceof Enumerable ?
                        $collection->all() : (method_exists($collection, 'all') ?
                            $collection->all() : $collection)),
                function ($carry, $value) {
                    $this->forwardCallTo(
                        $value,
                        EloquentQueryBuilderMethodsEnum::DELETE,
                        []
                    );
                    $carry += 1;
                    return $carry;
                },
                0
            );
        }
    }

    /**
     * Run an aggregation method on a query builder result
     *
     * @param array $query
     * @param string $aggregation
     * @return int|mixed
     */
    public function selectAggregate(array $query = [], string $aggregation = DatabaseQueryBuilderAggregationMethodsEnum::COUNT)
    {
        if (!in_array($aggregation, static::AGGREGATE_METHODS)) {
            throw new InvalidArgumentException('The provided method is not part of Illuminate aggregation framework on Query Builder Class');
        }
        return $this->forwardCallTo(
            array_reduce(drewlabs_core_array_is_no_assoc_array_list($query) ? $query : [$query], function ($model, $q) {
                return (new CustomQueryCriteria($q))->apply($model);
            }, drewlabs_core_create_attribute_getter('model', null)($this)),
            $aggregation,
            []
        );
    }

    public function select(...$args)
    {
        return $this->model->getConnection()->transaction(function () use ($args) {
            return $this->overload($args, [
                'selectV1',
                'selectV1_1',
                'selectV2',
                'selectV2_1',
                'selectV3',
                'selectV5',
                'selectV6',
                'selectV8',
                'selectV0'
            ]);
        });
    }

    /**
     *
     * @return Model|mixed
     */
    public function selectV0()
    {
        $callback = $callback ?? function ($value) {
            return $value;
        };
        return $callback($this->selectV3([]));
    }

    /**
     *
     * @param string $id
     * @param array $columns
     * @param \Closure $callback
     * @return Model|mixed
     */
    public function selectV1(string $id, array $columns, \Closure $callback = null)
    {
        $callback = $callback ?? function ($value) {
            return $value;
        };
        $collection =  $this->selectV5(
            [
                'where' => [$this->model->getPrimaryKey(), $id],
            ],
            $columns ?? ['*']
        )->getCollection();
        return $callback(is_array($collection) ? (new SimpleCollection($collection))->first() : (method_exists($collection, 'first') ? $collection->first() : $collection));
    }

    /**
     *
     * @param string $id
     * @param \Closure $callback
     * @return Model|mixed
     */
    public function selectV1_1(string $id, \Closure $callback = null)
    {
        return $this->selectV1($id, ['*'], $callback);
    }

    /**
     *
     * @param integer $id
     * @param array $columns
     * @param \Closure|null $callback
     * @return Model|mixed
     */
    public function selectV2(int $id, array $columns, \Closure $callback = null)
    {
        return $this->selectV1((string)$id, $columns, $callback);
    }

    /**
     *
     * @param integer $id
     * @param \Closure|null $callback
     * @return Model|mixed
     */
    public function selectV2_1(int $id, \Closure $callback = null)
    {
        return $this->selectV1((string)$id, ['*'], $callback);
    }

    /**
     *
     * @param array $query
     * @param \Closure|null $callback
     * @return DataProviderQueryResultInterface|mixed
     */
    public function selectV3(array $query, \Closure $callback = null)
    {
        return $this->selectV5($query, ['*'], $callback);
    }

    /**
     *
     * @param array $query
     * @param array $columns
     * @param \Closure|null $callback
     * @return DataProviderQueryResultInterface
     */
    public function selectV5(array $query, array $columns, \Closure $callback = null)
    {
        $callback = $callback ?? function ($value) {
            return $value;
        };
        $builder = array_reduce(
            drewlabs_core_array_is_no_assoc_array_list($query) ? $query : [$query],
            function ($model, $q) {
                return (new CustomQueryCriteria($q))->apply($model);
            },
            drewlabs_core_create_attribute_getter('model', null)($this)
        );
        $model_relations = method_exists($this->model, 'getModelRelationLoadersNames') ? call_user_func([$this->model, 'getModelRelationLoadersNames']) : [];
        [$columns_, $relations] = SelectQueryColumnsHelper::asTuple(
            $columns,
            $this->model->getDeclaredColumns(),
            $model_relations
        );
        return $callback(
            SelectQueryResult(
                new DataProviderQueryResult(
                    $this->forwardCallTo(
                        !empty($relations) ? $this->forwardCallTo(
                            $builder,
                            'with',
                            [$relations]
                        ) : $builder,
                        EloquentQueryBuilderMethodsEnum::SELECT,
                        [empty($columns_) || !empty($relations) ? ['*'] : $columns_]
                    )
                )
            )->each(function ($value) use ($columns_, $relations) {
                if (!empty($relations)) {
                    $columns = empty($columns_) ? $value->getHidden() :
                        array_diff(
                            $value->getDeclaredColumns() ?? [],
                            array_filter($columns_ ?? [], function ($key) {
                                return (null !== $key) && ($key !== '*');
                            })
                        );
                    return $value->setHidden($columns);
                }
                return $value;
            })->value(),
        );
    }

    /**
     * Handle pagination functionality
     * 
     * @param array $query
     * @param int $per_page
     * @param int $page
     * @param \Closure|null $callback
     * @return Paginator
     */
    public function selectV6(array $query, int $per_page, int $page = null, \Closure $callback = null)
    {
        return $this->selectV8($query, $per_page, ['*'], $page, $callback);
    }

    /**
     * Handle pagination functionality
     * 
     * @param array $query
     * @param int $per_page
     * @param array $columns
     * @param int|null $page
     * @param \Closure|null $callback
     * @return Paginator
     */
    public function selectV8(array $query, int $per_page, array $columns, int $page = null, \Closure $callback = null)
    {
        $callback = $callback ?? function ($value) {
            return $value;
        };
        $builder = array_reduce(
            drewlabs_core_array_is_no_assoc_array_list($query) ? $query : [$query],
            function ($model, $q) {
                return (new CustomQueryCriteria($q))->apply($model);
            },
            drewlabs_core_create_attribute_getter('model', null)($this)
        );
        // TODO : Get model relations
        $model_relations = method_exists($this->model, 'getModelRelationLoadersNames') ? call_user_func([$this->model, 'getModelRelationLoadersNames']) : [];
        [$columns_, $relations] = SelectQueryColumnsHelper::asTuple(
            $columns,
            $this->model->getDeclaredColumns(),
            $model_relations
        );
        return $callback(
            SelectQueryResult(
                new DataProviderQueryResult(
                    $this->forwardCallTo(
                        !empty($relations) ? $this->forwardCallTo(
                            $builder,
                            'with',
                            [$relations]
                        ) : $builder,
                        EloquentQueryBuilderMethodsEnum::PAGINATE,
                        [$per_page, empty($columns_) || !empty($relations) ? ['*'] : $columns_, null, $page ?? 1]
                    )
                )
            )->each(function ($value) use ($columns_, $relations) {
                if (!empty($relations)) {
                    $columns = empty($columns_) ? $value->getHidden() :
                        array_diff(
                            $value->getDeclaredColumns() ?? [],
                            array_filter($columns_ ?? [], function ($key) {
                                return (null !== $key) && ($key !== '*');
                            })
                        );
                    return $value->setHidden($columns);
                }
                return $value;
            })->value()
        );
    }

    public function update(...$args)
    {
        return $this->model->getConnection()->transaction(function () use ($args) {
            return $this->overload($args, [
                'updateV2',
                'updateV3',
                'updateV4',
                'updateV5',
                'updateV6'
            ]);
        });
    }

    public function updateV2(array $query, $attributes = [], bool $batch = false)
    {
        $is_array_list = drewlabs_core_array_is_no_assoc_array_list($query);
        if ($batch) {
            return $this->forwardCallTo(
                array_reduce($is_array_list ? $query : [$query], function ($model, $q) {
                    return (new CustomQueryCriteria($q))->apply($model);
                }, drewlabs_core_create_attribute_getter('model', null)($this)),
                EloquentQueryBuilderMethodsEnum::UPDATE,
                [$this->parseAttributes(($attributes instanceof Model) ? $attributes->toArray() : $attributes)]
            );
        } else {
            // Select the matching columns
            $collection = $this->selectV3($query, function ($result) {
                return $result->getCollection();
            });
            // Loop through the matching columns and update each
            return array_reduce(
                is_array($collection) ?
                    $collection : ($collection instanceof Enumerable ?
                        $collection->all() : (method_exists($collection, 'all') ?
                            $collection->all() : $collection)),
                function ($carry, $value) use ($attributes) {
                    $this->forwardCallTo(
                        $value,
                        EloquentQueryBuilderMethodsEnum::UPDATE,
                        [$this->parseAttributes(($attributes instanceof Model) ? $attributes->toArray() : $attributes)]
                    );
                    $carry += 1;
                    return $carry;
                },
                0
            );
        }
    }

    public function updateV3(int $id, $attributes, \Closure $callback = null)
    {
        return $this->updateV6((string)$id, $attributes, [], $callback);
    }
    public function updateV4(int $id, $attributes, $params, \Closure $callback = null)
    {
        return $this->updateV6((string)$id, $attributes, $params, $callback);
    }
    public function updateV5(string $id, $attributes, \Closure $callback = null)
    {
        return $this->updateV6((string)$id, $attributes, [], $callback);
    }
    public function updateV6(string $id, $attributes, $params, \Closure $callback = null)
    {
        $callback = $callback ?? function ($value) {
            return $value;
        };
        #region Update Handler func
        $update_model_func = function ($key, array $values) use ($callback) {
            return function (\Closure $cb = null)  use ($key, $values, $callback) {
                $this->updateV2(
                    [
                        'where' => ['id', $key]
                    ],
                    $this->parseAttributes($values)
                );
                $p = array_slice(func_get_args(), 1);
                call_user_func($cb ?? function () {
                }, ...$p);
                // Find the model by it id if the count is equal to 1
                return $callback($this->select($key));
            };
        };
        # endregion Update handler fund
        $that = $this;
        // Parse the params in order to get the method and upsert value
        $params = drewlabs_database_parse_update_handler_params($params);
        $method = $params['method'];
        $upsert = $params['upsert'] ?? false;
        return is_string($method) && drewlabs_database_is_dynamic_update_method($method) ?
            $update_model_func($id, $attributes)(function () use ($attributes, $upsert, $that, $method) {
                drewlabs_database_upsert_relations_after_create(
                    drewlabs_core_create_attribute_getter('model', null)($that),
                    array_slice(drewlabs_database_parse_dynamic_callback($method), 1),
                    $attributes,
                    $upsert
                );
            }) : $update_model_func($id, $attributes)();
    }

    /**
     * @inheritDoc
     */
    public function createRepository()
    {
        return new IlluminateModelRepository($this->model_class);
    }

    /**
     *
     * @param array $value
     * @return array
     */
    private function parseAttributes(array $value)
    {
        return Container::getInstance()->make(ModelAttributesParser::class)->setModel(drewlabs_core_create_attribute_getter('model', null)($this))->setModelInputState($value)->getModelInputState();
    }
}
