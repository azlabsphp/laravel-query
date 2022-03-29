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

use Drewlabs\Contracts\Data\Model\HasRelations;
use Drewlabs\Packages\Database\EloquentQueryBuilderMethods;
use Drewlabs\Packages\Database\EnumerableQueryResult;
use Drewlabs\Packages\Database\Helpers\SelectQueryColumnsHelper;

use function Drewlabs\Packages\Database\Proxy\ModelFiltersHandler;
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
                'selectV0',
            ]);
        });
    }

    public function selectOne(...$args)
    {
        return $this->model->getConnection()->transaction(function () use ($args) {
            return $this->overload(
                $args,
                [
                    function (array $query, ?\Closure $callback = null) {
                        $callback = $callback ?? static function ($value) {
                            return $value;
                        };
                        return $callback(
                            $this->createSelector(
                                $query,
                                ['*']
                            )(function ($builder, $columns_) {
                                return [
                                    $this->proxy(
                                        $builder,
                                        EloquentQueryBuilderMethods::SELECT_ONE,
                                        [$columns_]
                                    )
                                ];
                            })->first()
                        );
                    },
                    function (array $query, array $columns, ?\Closure $callback = null) {
                        $callback = $callback ?? static function ($value) {
                            return $value;
                        };
                        return $callback(
                            $this->createSelector(
                                $query,
                                $columns
                            )(function ($builder, $columns_) {
                                return [
                                    $this->proxy(
                                        $builder,
                                        EloquentQueryBuilderMethods::SELECT_ONE,
                                        [$columns_]
                                    )
                                ];
                            })->first()
                        );
                    },
                    function (?\Closure $callback = null) {
                        $callback = $callback ?? static function ($value) {
                            return $value;
                        };
                        return $callback(
                            $this->createSelector(
                                [],
                                ['*']
                            )(function ($builder, $columns_) {
                                return [
                                    $this->proxy(
                                        $builder,
                                        EloquentQueryBuilderMethods::SELECT_ONE,
                                        [$columns_]
                                    )
                                ];
                            })->first()
                        );
                    },
                ]
            );
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

    public function selectV1(string $id, array $columns, ?\Closure $callback = null)
    {
        $callback = $callback ?? static function ($value) {
            return $value;
        };

        return $callback(
            $this->createSelector(
                ['where' => [$this->model->getPrimaryKey(), $id]],
                $columns ?? ['*']
            )(function ($builder, $columns_) {
                return $this->proxy(
                    $builder,
                    EloquentQueryBuilderMethods::SELECT,
                    [$columns_]
                );
            })->first()
        );
    }

    public function selectV1_1(string $id, ?\Closure $callback = null)
    {
        return $this->selectV1($id, ['*'], $callback);
    }

    public function selectV2(int $id, array $columns, ?\Closure $callback = null)
    {
        return $this->selectV1((string) $id, $columns, $callback);
    }

    public function selectV2_1(int $id, ?\Closure $callback = null)
    {
        return $this->selectV1((string) $id, ['*'], $callback);
    }

    public function selectV3(array $query, ?\Closure $callback = null)
    {
        return $this->createSelector(
            $query,
            ['*'],
            $callback
        )(function ($builder, $columns_) {
            return $this->proxy(
                $builder,
                EloquentQueryBuilderMethods::SELECT,
                [$columns_]
            );
        });
    }

    public function selectV5(array $query, array $columns, ?\Closure $callback = null)
    {
        return $this->createSelector(
            $query,
            $columns,
            $callback
        )(function ($builder, $columns_) {
            return $this->proxy(
                $builder,
                EloquentQueryBuilderMethods::SELECT,
                [$columns_]
            );
        });
    }

    public function selectV6(array $query, int $per_page, ?int $page = null, ?\Closure $callback = null)
    {
        return $this->createSelector(
            $query,
            ['*'],
            $callback
        )(function ($builder, $columns_) use ($per_page, $page) {
            return $this->proxy(
                $builder,
                EloquentQueryBuilderMethods::PAGINATE,
                [$per_page, $columns_, null, $page ?? 1]
            );
        });
    }

    public function selectV8(array $query, int $per_page, array $columns, ?int $page = null, ?\Closure $callback = null)
    {
        return $this->createSelector(
            $query,
            $columns,
            $callback
        )(function ($builder, $columns_) use ($per_page, $page) {
            return $this->proxy(
                $builder,
                EloquentQueryBuilderMethods::PAGINATE,
                [$per_page, $columns_, null, $page ?? 1]
            );
        });
    }

    private function createSelector(array $query, array $columns, ?\Closure $callback = null)
    {
        return function (\Closure $selector) use ($query, $columns, $callback) {
            $callback = $callback ?? static function ($value) {
                return $value;
            };
            $model_relations = method_exists($this->model, 'getModelRelationLoadersNames') || ($this->model instanceof HasRelations) ? $this->model->getModelRelationLoadersNames() : [];
            [$columns_, $relations] = SelectQueryColumnsHelper::asTuple(
                $columns,
                $this->model->getDeclaredColumns(),
                $model_relations
            );
            $primaryKey = $this->model->getPrimaryKey();
            $builder = array_reduce(
                drewlabs_core_array_is_no_assoc_array_list($query) ? $query : [$query],
                static function ($model, $q) {
                    return ModelFiltersHandler($q)->apply($model);
                },
                drewlabs_core_create_attribute_getter('model', null)($this)
            );
            if (!empty($relations)) {
                $builder = $this->proxy($builder, 'with', [$relations]);
            }
            return $callback(
                SelectQueryResult(
                    new EnumerableQueryResult(
                        $selector(
                            $builder,
                            empty($columns_) || !empty($relations) ? ['*'] : drewlabs_core_array_unique(array_merge($columns_ ?? [], [$primaryKey]))
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
        };
    }
}
