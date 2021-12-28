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

namespace Drewlabs\Packages\Database\Traits;

use Drewlabs\Contracts\Data\DataProviderQueryResultInterface;
use Drewlabs\Contracts\Data\Model\Relatable;
use Drewlabs\Core\Data\DataProviderQueryResult;
use Drewlabs\Packages\Database\EloquentQueryBuilderMethodsEnum;
use Drewlabs\Packages\Database\Extensions\CustomQueryCriteria;
use Drewlabs\Packages\Database\Helpers\SelectQueryColumnsHelper;
use function Drewlabs\Packages\Database\Proxy\SelectQueryResult;

use Drewlabs\Support\Collections\SimpleCollection;

use Illuminate\Contracts\Pagination\Paginator;

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
                'selectV0',
            ]);
        });
    }

    /**
     * @return Model|mixed
     */
    public function selectV0()
    {
        $callback = $callback ?? static function ($value) {
            return $value;
        };

        return $callback($this->selectV3([]));
    }

    /**
     * @param \Closure $callback
     *
     * @return Model|mixed
     */
    public function selectV1(string $id, array $columns, ?\Closure $callback = null)
    {
        $callback = $callback ?? static function ($value) {
            return $value;
        };
        return $callback(
            $this->selectV5(
                [
                    'where' => [$this->model->getPrimaryKey(), $id],
                ],
                $columns ?? ['*']
            )->first()
        );
    }

    /**
     * @param \Closure $callback
     *
     * @return Model|mixed
     */
    public function selectV1_1(string $id, ?\Closure $callback = null)
    {
        return $this->selectV1($id, ['*'], $callback);
    }

    /**
     * @return Model|mixed
     */
    public function selectV2(int $id, array $columns, ?\Closure $callback = null)
    {
        return $this->selectV1((string) $id, $columns, $callback);
    }

    /**
     * @return Model|mixed
     */
    public function selectV2_1(int $id, ?\Closure $callback = null)
    {
        return $this->selectV1((string) $id, ['*'], $callback);
    }

    /**
     * @return DataProviderQueryResultInterface|mixed
     */
    public function selectV3(array $query, ?\Closure $callback = null)
    {
        return $this->selectV5($query, ['*'], $callback);
    }

    /**
     * @return mixed
     */
    public function selectV5(array $query, array $columns, ?\Closure $callback = null)
    {
        $callback = $callback ?? static function ($value) {
            return $value;
        };
        $builder = array_reduce(
            drewlabs_core_array_is_no_assoc_array_list($query) ? $query : [$query],
            static function ($model, $q) {
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
                    $this->proxy(
                        !empty($relations) ? $this->proxy(
                            $builder,
                            'with',
                            [$relations]
                        ) : $builder,
                        EloquentQueryBuilderMethodsEnum::SELECT,
                        [empty($columns_) || !empty($relations) ? ['*'] : drewlabs_core_array_unique(array_merge($columns_ ?? [], [$primaryKey]))]
                    )
                )
            )->each(static function ($value) use ($columns_, $relations, $primaryKey) {
                if (!empty($relations)) {
                    $columns = empty($columns_) ? $value->getHidden() :
                        array_diff(
                            // Filter out the primary key in order to include it no matter what
                            drewlabs_core_array_except($value->getDeclaredColumns(), [$primaryKey]) ?? [],
                            array_filter($columns_ ?? [], static function ($key) {
                                return (null !== $key) && ('*' !== $key);
                            })
                        );

                    return $value->setHidden($columns);
                }

                return $value;
            })->value(),
        );
    }

    /**
     * Handle pagination functionality.
     *
     * @param int $page
     *
     * @return Paginator
     */
    public function selectV6(array $query, int $per_page, ?int $page = null, ?\Closure $callback = null)
    {
        return $this->selectV8($query, $per_page, ['*'], $page, $callback);
    }

    /**
     * Handle pagination functionality.
     *
     * @return Paginator
     */
    public function selectV8(array $query, int $per_page, array $columns, ?int $page = null, ?\Closure $callback = null)
    {
        $callback = $callback ?? static function ($value) {
            return $value;
        };
        $builder = array_reduce(
            drewlabs_core_array_is_no_assoc_array_list($query) ? $query : [$query],
            static function ($model, $q) {
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
                    $this->proxy(
                        !empty($relations) ? $this->proxy(
                            $builder,
                            'with',
                            [$relations]
                        ) : $builder,
                        EloquentQueryBuilderMethodsEnum::PAGINATE,
                        [$per_page, empty($columns_) || !empty($relations) ? ['*'] : drewlabs_core_array_unique(array_merge($columns_ ?? [], [$primaryKey])), null, $page ?? 1]
                    )
                )
            )->each(static function ($value) use ($columns_, $relations, $primaryKey) {
                if (!empty($relations)) {
                    $columns = empty($columns_) ? $value->getHidden() :
                        array_diff(
                            // Filter out the primary key in order to include it no matter what
                            drewlabs_core_array_except($value->getDeclaredColumns(), [$primaryKey]) ?? [],
                            array_filter($columns_ ?? [], static function ($key) {
                                return (null !== $key) && ('*' !== $key);
                            })
                        );

                    return $value->setHidden($columns);
                }

                return $value;
            })->value()
        );
    }
}
