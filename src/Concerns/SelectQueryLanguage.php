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

namespace Drewlabs\Packages\Concerns;

use Drewlabs\Core\Helpers\Arr;
use Drewlabs\Query\Columns;
use Drewlabs\Query\Contracts\FiltersInterface;
use Drewlabs\Query\EnumerableResult;

use function Drewlabs\Packages\Database\Proxy\SelectQueryResult;

trait SelectQueryLanguage
{
    public function select(...$args)
    {
        return $this->overload($args, [
            // Overload
            function (string $id, array $columns, \Closure $callback = null) {
                $callback = $callback ?? static function ($value) {
                    return $value;
                };
                return $callback($this->createSelector(['where' => [$this->model->getPrimaryKey(), $id]], $columns ?? ['*'])(function ($builder, $columns) {
                    return $builder->get($columns);
                })->first());
            },

            // Overload
            function (string $id, \Closure $callback = null) {
                $callback = $callback ?? static function ($value) {
                    return $value;
                };
                return $callback($this->createSelector(['where' => [$this->model->getPrimaryKey(), $id]], ['*'])(function ($builder, $columns) {
                    return $builder->get($columns);
                })->first());
            },

            // Overload
            function (int $id, array $columns, \Closure $callback = null) {
                $callback = $callback ?? static function ($value) {
                    return $value;
                };
                return $callback($this->createSelector(['where' => [$this->model->getPrimaryKey(), $id]], $columns ?? ['*'])(function ($builder, $columns) {
                    return $builder->get($columns);
                })->first());
            },

            // Overload
            function (int $id, \Closure $callback = null) {
                $callback = $callback ?? static function ($value) {
                    return $value;
                };
                return $callback($this->createSelector(['where' => [$this->model->getPrimaryKey(), $id]], ['*'])(function ($builder, $columns) {
                    return $builder->get($columns);
                })->first());
            },

            // Overload
            function (array $query, \Closure $callback = null) {
                return $this->createSelector($query, ['*'], $callback)(function ($builder, $columns) {
                    return $$builder->get($columns);
                });
            },

            // Overload
            function (array $query, array $columns, \Closure $callback = null) {
                return $this->createSelector($query, $columns, $callback)(function ($builder, $columns) {
                    return $builder->get($columns);
                });
            },

            // Overload
            function (array $query, int $per_page, int $page = null, \Closure $callback = null) {
                return $this->createSelector($query, ['*'], $callback)(function ($builder, $columns) use ($per_page, $page) {
                    return $builder->paginate($per_page, $columns, null, $page ?? 1);
                });
            },

            // Overload
            function (array $query, int $per_page, array $columns, int $page = null, \Closure $callback = null) {
                return $this->createSelector($query, $columns, $callback)(function ($builder, $columns) use ($per_page, $page) {
                    return $builder->paginate($per_page, $columns, null, $page ?? 1);
                });
            },

            // Overload
            function (FiltersInterface $query, \Closure $callback = null) {
                return $this->createSelector($query, ['*'], $callback)(function ($builder, $columns) {
                    return $builder->get($columns);
                });
            },

            // Overload
            function (FiltersInterface $query, array $columns, \Closure $callback = null) {
                return $this->createSelector($query, $columns, $callback)(function ($builder, $columns) {
                    return $builder->get($columns);
                });
            },

            // Overload
            function (FiltersInterface $query, int $per_page, int $page = null, \Closure $callback = null) {
                return $this->createSelector($query, ['*'], $callback)(function ($builder, $columns) use ($per_page, $page) {
                    return $builder->paginate($per_page, $columns, null, $page ?? 1);
                });
            },

            // Overload
            function (FiltersInterface $query, int $per_page, array $columns, int $page = null, \Closure $callback = null) {
                return $this->createSelector($query, $columns, $callback)(function ($builder, $columns) use ($per_page, $page) {
                    return $builder->paginate($per_page, $columns, null, $page ?? 1);
                });
            },
            function (\Closure $callback = null) {
                $callback = $callback ?? static function ($value) {
                    return $value;
                };
                return $callback($this->select__3([]));
            },
        ]);
    }

    public function selectFirst(...$args)
    {
        return $this->overload(
            $args,
            [
                function (array $query, \Closure $callback = null) {
                    $callback = $callback ?? static function ($value) {
                        return $value;
                    };
                    return $callback($this->createSelector($query, ['*'])(function ($builder, $columns) {
                        return [$builder->first($columns)];
                    })->first());
                },
                function (array $query, array $columns, \Closure $callback = null) {
                    $callback = $callback ?? static function ($value) {
                        return $value;
                    };
                    return $callback($this->createSelector($query, $columns)(function ($builder, $columns) {
                        return [$builder->first($columns)];
                    })->first());
                },
                function (\Closure $callback = null) {
                    $callback = $callback ?? static function ($value) {
                        return $value;
                    };
                    return $callback($this->createSelector([], ['*'])(function ($builder, $columns) {
                        return [$builder->first($columns)];
                    })->first());
                },
            ]
        );
    }

    /**
     * @param array|FiltersInterface $query
     *
     * @return \Closure
     */
    private function createSelector($query, array $columns, \Closure $callback = null)
    {
        return function (\Closure $selector) use ($query, $columns, $callback) {
            $callback = $callback ?? static function ($value) {
                return $value;
            };
            // We initialize filtering attributes for optimization instead of querying them on each
            // model instance returned after the query
            $model_relations = $this->queryable->getDeclaredRelations();
            $declared = $this->queryable->getDeclaredColumns();
            $primaryKey = $this->queryable->getPrimaryKey();
            $exceptions = $this->queryable->getHidden();
            [$columns_, $relations] = Columns::new($columns)->tuple($declared, $model_relations);
            // We prepare the query builder object
            $builder = $this->builderFactory()($this->queryable, $query);

            // Add relationship queries to the builder if the relationship array is not empty
            if (!empty($relations)) {
                $builder = $builder->with([$relations]);
            }

            // Create set columns that must not be included in the output result
            $excepts = array_unique((!empty($columns_) && !\in_array('*', $columns_, true)) ? array_merge($exceptions, array_diff(Arr::filter($declared, static function ($column) use ($primaryKey) {
                return $column !== $primaryKey;
            }), [...$columns_, '*'])) : $exceptions);

            return $callback(SelectQueryResult(
                new EnumerableResult($selector($builder, empty($columns_) || !empty($relations) ? ['*'] : array_unique(array_merge($columns_ ?? [], [$primaryKey]))))
            )->map(static function ($value) use ($excepts) {
                return $value->setHidden($excepts);
            })->get());
        };
    }
}
