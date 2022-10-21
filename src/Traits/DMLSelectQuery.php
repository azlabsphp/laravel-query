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

use Closure;
use Drewlabs\Contracts\Data\Filters\FiltersInterface;
use Drewlabs\Contracts\Data\Model\HasRelations;
use Drewlabs\Core\Helpers\Arr;
use Drewlabs\Packages\Database\EloquentQueryBuilderMethods;
use Drewlabs\Packages\Database\EnumerableQueryResult;
use Drewlabs\Packages\Database\Helpers\QueryColumns;

use function Drewlabs\Packages\Database\Proxy\SelectQueryResult;

trait DMLSelectQuery
{
    use PreparesQueryBuilder;

    public function select(...$args)
    {
        return $this->model->getConnection()->transaction(function () use ($args) {
            return $this->overload($args, [
                'selectV1',
                'selectV1_1',
                'selectV2',
                'selectV2_1',
                'selectV3',
                'selectV3_1',
                'selectV4',
                'selectV4_1',
                'selectV5',
                'selectV5_1',
                'selectV6',
                'selectV6_1',
                'selectV0',
            ]);
        });
    }

    public function selectOne(...$args)
    {
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
                                ),
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
                                ),
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
                                ),
                            ];
                        })->first()
                    );
                },
            ]
        );
    }

    private function selectV0(?\Closure $callback = null)
    {
        $callback = $callback ?? static function ($value) {
            return $value;
        };

        return $callback($this->selectV3([]));
    }

    private function selectV1(string $id, array $columns, ?\Closure $callback = null)
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

    private function selectV1_1(string $id, ?\Closure $callback = null)
    {
        return $this->selectV1($id, ['*'], $callback);
    }

    private function selectV2(int $id, array $columns, ?\Closure $callback = null)
    {
        return $this->selectV1((string) $id, $columns, $callback);
    }

    private function selectV2_1(int $id, ?\Closure $callback = null)
    {
        return $this->selectV1((string) $id, ['*'], $callback);
    }

    private function selectV3(array $query, ?\Closure $callback = null)
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

    private function selectV3_1(array $query, array $columns, ?\Closure $callback = null)
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

    private function selectV4(array $query, int $per_page, ?int $page = null, ?\Closure $callback = null)
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

    private function selectV4_1(array $query, int $per_page, array $columns, ?int $page = null, ?\Closure $callback = null)
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

    private function selectV5(FiltersInterface $query, ?\Closure $callback = null)
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

    private function selectV5_1(FiltersInterface $query, array $columns, ?\Closure $callback = null)
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

    private function selectV6(FiltersInterface $query, int $per_page, ?int $page = null, ?\Closure $callback = null)
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

    private function selectV6_1(FiltersInterface $query, int $per_page, array $columns, ?int $page = null, ?\Closure $callback = null)
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

    /**
     * 
     * @param array|FiltersInterface $query 
     * @param array $columns 
     * @param null|Closure $callback 
     * @return Closure 
     */
    private function createSelector($query, array $columns, ?\Closure $callback = null)
    {
        return function (\Closure $selector) use ($query, $columns, $callback) {
            $callback = $callback ?? static function ($value) {
                return $value;
            };
            // We initialize filtering attributes for optimization instead of querying them on each
            // model instance returned after the query
            $model = drewlabs_core_create_attribute_getter('model', null)($this);
            $model_relations = method_exists($model, 'getDeclaredRelations') || ($model instanceof HasRelations) ? $model->getDeclaredRelations() : [];
            $declared_columns = $model->getDeclaredColumns();
            $primaryKey = $model->getPrimaryKey();
            $hidden_columns = $model->getHidden();
            [$columns_, $relations] = QueryColumns::asTuple($columns, $declared_columns, $model_relations);
            // We prepare the query builder object    
            $builder = $this->prepareQueryBuilder($model, $query);

            // Add relationship queries to the builder if the relationship array is not empty
            if (!empty($relations)) {
                $builder = $this->proxy($builder, 'with', [$relations]);
            }

            // Create set columns that must not be included in the output result
            $except_columns = array_unique((!empty($columns_) && !in_array('*', $columns_)) ?
                array_merge($hidden_columns, array_diff(Arr::filter($declared_columns, function ($column) use ($primaryKey) {
                    return $column !== $primaryKey;
                }), [...$columns_, '*'])) :
                $hidden_columns);
            return $callback(
                SelectQueryResult(
                    new EnumerableQueryResult(
                        $selector(
                            $builder,
                            empty($columns_) || !empty($relations) ? ['*'] : array_unique(array_merge($columns_ ?? [], [$primaryKey]))
                        )
                    )
                )->map(static function ($value) use ($except_columns) {
                    return $value->setHidden($except_columns);
                })->value(),
            );
        };
    }
}
