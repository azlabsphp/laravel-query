<?php

namespace Drewlabs\Packages\Database\Traits;

use Drewlabs\Contracts\Data\DataProviderQueryResultInterface;
use Drewlabs\Contracts\Data\Model\Relatable;
use Drewlabs\Core\Data\DataProviderQueryResult;
use Drewlabs\Packages\Database\EloquentQueryBuilderMethodsEnum;
use Drewlabs\Packages\Database\Extensions\CustomQueryCriteria;
use Drewlabs\Packages\Database\Helpers\SelectQueryColumnsHelper;
use Drewlabs\Support\Collections\SimpleCollection;
use Illuminate\Contracts\Pagination\Paginator;

use function Drewlabs\Packages\Database\Proxy\SelectQueryResult;

trait DMLSelectQuery
{
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
        $model_relations = method_exists($this->model, 'getModelRelationLoadersNames') || ($this->model instanceof Relatable) ? $this->model->getModelRelationLoadersNames() : [];
        [$columns_, $relations] = SelectQueryColumnsHelper::asTuple(
            $columns,
            $this->model->getDeclaredColumns(),
            $model_relations
        );
        $primaryKey = $this->model->getPrimaryKey();
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
                        [empty($columns_) || !empty($relations) ? ['*'] : drewlabs_core_array_unique(array_merge($columns_ ?? [], [$primaryKey]))]
                    )
                )
            )->each(function ($value) use ($columns_, $relations, $primaryKey) {
                if (!empty($relations)) {
                    $columns = empty($columns_) ? $value->getHidden() :
                        array_diff(
                            // Filter out the primary key in order to include it no matter what
                            drewlabs_core_array_except($value->getDeclaredColumns(), [$primaryKey]) ?? [],
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
        $model_relations = method_exists($this->model, 'getModelRelationLoadersNames') || ($this->model instanceof Relatable) ? $this->model->getModelRelationLoadersNames() : [];
        [$columns_, $relations] = SelectQueryColumnsHelper::asTuple(
            $columns,
            $this->model->getDeclaredColumns(),
            $model_relations
        );
        $primaryKey = $this->model->getPrimaryKey();
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
                        [$per_page, empty($columns_) || !empty($relations) ? ['*'] : drewlabs_core_array_unique(array_merge($columns_ ?? [], [$primaryKey])), null, $page ?? 1]
                    )
                )
            )->each(function ($value) use ($columns_, $relations, $primaryKey) {
                if (!empty($relations)) {
                    $columns = empty($columns_) ? $value->getHidden() :
                        array_diff(
                            // Filter out the primary key in order to include it no matter what
                            drewlabs_core_array_except($value->getDeclaredColumns(), [$primaryKey]) ?? [],
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
}
