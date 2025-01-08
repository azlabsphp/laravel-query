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

namespace Drewlabs\Laravel\Query;

use Closure;
use Drewlabs\Core\Helpers\Arr;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder;

/**
 * The goal of the model relation handler implementation class is to
 * provide an API for inserting a model relations values after model
 * was inserted into the database.
 */
class QueryableRelations
{
    /**
     * @var Model
     */
    private $queryable;

    /**
     * Creates class instance.
     *
     * @param mixed $model
     */
    private function __construct($model)
    {
        $this->queryable = $model;
    }

    /**
     * Creates a new class instance.
     *
     * @param mixed $model
     *
     * @return QueryableRelations
     */
    public static function new($model)
    {
        return new self($model);
    }

    /**
     * Creates model child relations based on user provided parameters.
     *
     * @return void
     */
    public function create(array $relations, array $attributes = [], bool $batch = false)
    {
        if (empty($relations)) {
            return;
        }
        [$relations, $composed] = $this->groupRelations($relations);
        foreach ($relations as $relation) {
            if (!(method_exists($this->queryable, $relation) && !empty($attributes[$relation] ?? []))) {
                continue;
            }
            Arr::isnotassoclist($attributes[$relation] ?? []) ?
                (
                    $batch ?
                    $this->createManyBatch($this->queryable->$relation(), $attributes[$relation]) :
                    $this->createMany($this->queryable->$relation(), $attributes[$relation], $this->resolveRelations($composed, $relation))
                ) :
                $this->createOne($this->queryable->$relation(), $attributes[$relation], $this->resolveRelations($composed, $relation));
        }
    }

    /**
     * Update model child relations based on user provided values.
     *
     * @return void
     */
    public function update(array $relations, array $attributes)
    {
        if (empty($relations)) {
            return;
        }
        // list($relations, $composed) = $this->groupRelations($relations);
        foreach ($relations as $relation) {
            $exists = method_exists($this->queryable, $relation) && !empty($attributes[$relation] ?? []);
            if (!$exists) {
                continue;
            }
            //  $this->resolveRelations($composed, $relation)
            $this->updateRelation($this->queryable->$relation(), $attributes[$relation]);
        }
    }

    /**
     * Refresh the model child relations by deleting old ones and adding
     * new ones based on user's provided data.
     *
     * @return mixed
     */
    public function refresh(array $relations, array $attributes)
    {
        [$relations, $composed] = $this->groupRelations($relations);
        foreach ($relations as $relation) {
            $exists = method_exists($this->queryable, $relation) && !empty($attributes[$relation] ?? []);
            if (!$exists) {
                continue;
            }
            $this->refreshRelation($this->queryable->$relation(), $attributes[$relation], $this->resolveRelations($composed, $relation));
        }
    }

    /**
     * Call update or create method on the model instance.
     *
     * @param Builder|Relation $query
     *
     * @throws \LogicException
     *
     * @return mixed
     */
    private static function updateOrCreate($query, array $value = [])
    {
        if (1 < \count($value)) {
            return $query->updateOrCreate(
                ...static::formatUpsertAttributes(
                    $query,
                    $value[0],
                    $value[1]
                )
            );
        }
        if (1 === \count($value)) {
            return $query->updateOrCreate(
                ...static::formatUpsertAttributes(
                    $query,
                    $value[0],
                    $value[0]
                )
            );
        }
        throw new \LogicException('Expected '.__METHOD__.' to receive an array of 1 or 2 array values');
    }

    /**
     * Format attributes for create many query.
     *
     * @param Relation $instance
     *
     * @return array
     */
    private static function formatCreateManyAttributes($instance, array $attributes = [])
    {
        if ($instance instanceof BelongsToMany) {
            $out = [];
            foreach ($attributes as $value) {
                $pivot = $value['pivot'] ?? $value['joining'] ?? [];
                $attribute = Arr::except($value, ['pivot', 'joining']);
                $out[0][] = $attribute;
                $out[1][] = $pivot;
            }

            return $out;
        }

        return [$attributes];
    }

    /**
     * Format attribute before insert action.
     *
     * @param Relation $instance
     *
     * @return array
     */
    private static function formatCreateAttributes($instance, array $attributes = [])
    {
        return $instance instanceof BelongsToMany ? [Arr::except($attributes, ['pivot', 'joining']), $attributes['pivot'] ?? $attributes['joining'] ?? []] : [$attributes];
    }

    /**
     * Format attribute before upserting.
     *
     * @param Relation $instance
     *
     * @return array
     */
    private static function formatUpsertAttributes($instance, array $attributes, array $values = [])
    {
        return $instance instanceof BelongsToMany ? [$attributes, Arr::except($values, ['pivot', 'joining']), $values['pivot'] ?? $values['joining'] ?? []] : [$attributes, $values];
    }

    /**
     * Update model relation.
     *
     * @param Relation $instance
     *
     * @throws \LogicException
     *
     * @return void
     */
    private function updateRelation($instance, array $values)
    {
        if (Arr::isassoc($values)) {
            $this->getInstanceCopy($instance)->update($values);

            return;
        }
        foreach (Arr::isnotassoclist($values[0] ?? []) ? $values : [$values] as $value) {
            static::updateOrCreate($this->getInstanceCopy($instance), $value);
        }
    }

    /**
     * Refresh model relations.
     *
     * @param Relation $instance
     *
     * @return void
     */
    private function refreshRelation($instance, array $values, array $relations = [])
    {
        if (Arr::isnotassoclist($values)) {
            $this->getInstanceCopy($instance)->delete();
            $this->getInstanceCopy($instance)->createMany(...static::formatCreateManyAttributes($instance, $values));
        } else {
            $this->getInstanceCopy($instance)->delete();
            $this->createOne($instance, $values, $relations);
        }
    }

    private function createMany($instance, array $attributes, array $relations)
    {
        foreach ($attributes as $current) {
            $result = Arr::isnotassoclist($current) ? static::updateOrCreate($this->getInstanceCopy($instance), $current) : $this->getInstanceCopy($instance)->create(...static::formatCreateAttributes($instance, $current));
            // Recursively execute the create implementation relations attached to the model
            self::new($result)->create($relations, $current);
        }
    }

    /**
     * Insert values in database in batches.
     *
     * @param Relation $instance
     *
     * @return void
     */
    private function createManyBatch($instance, array $attributes)
    {
        $this->getInstanceCopy($instance)->createMany(...static::formatCreateManyAttributes($instance, $attributes));
    }

    /**
     * Insert row into database table.
     *
     * @param Relation $instance
     *
     * @throws \LogicException
     *
     * @return void
     */
    private function createOne($instance, array $attributes, array $relations)
    {
        $result = $this->getInstanceCopy($instance)->create(...static::formatCreateAttributes($instance, $attributes));
        // Recursively execute the create implementation relations attached to the model
        self::new($result)->create($relations, $attributes);
    }

    /**
     * Creates or Returns a copy of the query builder instance of the relation.
     *
     * @return Builder|Relation
     */
    private function getInstanceCopy(Relation $instance)
    {
        return clone $instance;
    }

    private function groupRelations(array $relations)
    {
        // #region We filter the componsed relation from default relations to recursively set create model relations
        $composed = array_filter($relations, static function ($current) {
            return \is_string($current) && str_contains($current, '.');
        });
        // #endregion We filter the componsed relation from default relations to recursively set create model relations
        return [array_diff($relations, $composed), $composed];
    }

    /**
     * Get relations that has the $relation value as parent relation.
     *
     * @param mixed $relation
     *
     * @return array
     */
    private function resolveRelations(array $relations, string $relation)
    {
        return iterator_to_array($this->findAll($relations, static function ($iterator) use ($relation) {
            return "$relation." === substr($iterator, 0, \strlen("$relation."));
        }, static function ($value) use ($relation) {
            return substr($value, \strlen("$relation."));
        }));
    }

    /**
     * Find all values matching user provided callback.
     *
     * @param Closure(T $value, $key):bool $callback
     * @param Closure(T $value, $key):mixed $callback
     *
     * @return \Traversable<T>
     */
    private function findAll(array $list, \Closure $callback, ?\Closure $project = null)
    {
        foreach ($list as $key => $value) {
            if ($callback($value, $key)) {
                yield $key => $project ? $project($value) : $value;
            }
        }
    }
}
