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

namespace Drewlabs\Laravel\Query\Concerns;

use Drewlabs\Core\Helpers\Arr;

use function Drewlabs\Laravel\Query\Proxy\SelectQueryResult;

use Drewlabs\Query\Columns;
use Drewlabs\Query\Contracts\FiltersInterface;
use Drewlabs\Query\Contracts\Queryable;
use Drewlabs\Query\EnumerableResult;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin \Drewlabs\Laravel\Query\Contracts\ProvidesFiltersFactory
 *
 * @property Queryable|Model queryable
 */
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

                return $callback($this->createSelector(['and' => [$this->queryable->getPrimaryKey(), $id]], $columns ?? ['*'])(static function ($builder, $columns) {
                    return $builder->get($columns);
                })->first());
            },

            // Overload
            function (string $id, \Closure $callback = null) {
                $callback = $callback ?? static function ($value) {
                    return $value;
                };

                return $callback($this->createSelector(['and' => [$this->queryable->getPrimaryKey(), $id]], ['*'])(static function ($builder, $columns) {
                    return $builder->get($columns);
                })->first());
            },

            // Overload
            function (int $id, array $columns, \Closure $callback = null) {
                $callback = $callback ?? static function ($value) {
                    return $value;
                };

                return $callback($this->createSelector(['and' => [$this->queryable->getPrimaryKey(), $id]], $columns ?? ['*'])(static function ($builder, $columns) {
                    return $builder->get($columns);
                })->first());
            },

            // Overload
            function (int $id, \Closure $callback = null) {
                $callback = $callback ?? static function ($value) {
                    return $value;
                };

                return $callback($this->createSelector(['and' => [$this->queryable->getPrimaryKey(), $id]], ['*'])(static function ($builder, $columns) {
                    return $builder->get($columns);
                })->first());
            },

            // Overload
            function (array $query, \Closure $callback = null) {
                return $this->createSelector($query, ['*'], $callback)(static function ($builder, $columns) {
                    return $builder->get($columns);
                });
            },

            // Overload
            function (array $query, array $columns, \Closure $callback = null) {
                return $this->createSelector($query, $columns, $callback)(static function ($builder, $columns) {
                    return $builder->get($columns);
                });
            },

            // Overload
            function (array $query, int $per_page, int $page = null, \Closure $callback = null) {
                return $this->createSelector($query, ['*'], $callback)(static function ($builder, $columns) use ($per_page, $page) {
                    return $builder->paginate($per_page, $columns, null, $page ?? 1);
                });
            },

            // Overload
            function (array $query, int $per_page, array $columns, int $page = null, \Closure $callback = null) {
                return $this->createSelector($query, $columns, $callback)(static function ($builder, $columns) use ($per_page, $page) {
                    return $builder->paginate($per_page, $columns, null, $page ?? 1);
                });
            },

            // Overload
            function (FiltersInterface $query, \Closure $callback = null) {
                return $this->createSelector($query, ['*'], $callback)(static function ($builder, $columns) {
                    return $builder->get($columns);
                });
            },

            // Overload
            function (FiltersInterface $query, array $columns, \Closure $callback = null) {
                return $this->createSelector($query, $columns, $callback)(static function ($builder, $columns) {
                    return $builder->get($columns);
                });
            },

            // Overload
            function (FiltersInterface $query, int $per_page, int $page = null, \Closure $callback = null) {
                return $this->createSelector($query, ['*'], $callback)(static function ($builder, $columns) use ($per_page, $page) {
                    return $builder->paginate($per_page, $columns, null, $page ?? 1);
                });
            },

            // Overload
            function (FiltersInterface $query, int $per_page, array $columns, int $page = null, \Closure $callback = null) {
                return $this->createSelector($query, $columns, $callback)(static function ($builder, $columns) use ($per_page, $page) {
                    return $builder->paginate($per_page, $columns, null, $page ?? 1);
                });
            },

            function (\Closure $callback = null) {
                return $this->createSelector([], ['*'], $callback)(static function ($builder, $columns) {
                    return $builder->get($columns);
                });
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

                    return $callback($this->createSelector($query, ['*'])(static function ($builder, $columns) {
                        return (null !== ($result = $builder->first($columns))) ? [$result] : [];
                    })->first());
                },
                function (array $query, array $columns, \Closure $callback = null) {
                    $callback = $callback ?? static function ($value) {
                        return $value;
                    };

                    return $callback($this->createSelector($query, $columns)(static function ($builder, $columns) {
                        return (null !== ($result = $builder->first($columns))) ? [$result] : [];
                    })->first());
                },
                function (\Closure $callback = null) {
                    $callback = $callback ?? static function ($value) {
                        return $value;
                    };

                    return $callback($this->createSelector([], ['*'])(static function ($builder, $columns) {
                        return (null !== ($result = $builder->first($columns))) ? [$result] : [];
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
            [$c, $relations] = Columns::new($columns)->tuple($declared, $model_relations);
            // We prepare the query builder object
            $builder = $this->builderFactory()($this->queryable, $query);

            // Add relationship queries to the builder if the relationship array is not empty
            $builder = !empty($relations) ? $builder->with($relations) : $builder;

            // Create set columns that must not be included in the output result
            $excepts = array_unique((!empty($c) && !\in_array('*', $c, true)) ? array_merge($exceptions, array_diff(Arr::filter($declared, static function ($column) use ($primaryKey) {
                return $column !== $primaryKey;
            }), [...$c, '*'])) : $exceptions);

            return $callback(SelectQueryResult(
                new EnumerableResult($selector($builder, empty($c) || !empty($relations) ? ['*'] : array_unique(array_merge($c ?? [], [$primaryKey]))))
            )->map(static function ($value) use ($excepts) {
                return $value->setHidden($excepts);
            })->get());
        };
    }
}
