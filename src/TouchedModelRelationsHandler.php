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
use Drewlabs\Core\Helpers\Arr;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use LogicException;

/**
 * The goal of the model relation handler implementation class is to
 * provide an API for inserting a model relations values after model
 * was inserted into the database.
 */
class TouchedModelRelationsHandler
{
    /**
     * @var mixed
     */
    private $model;

    private function __construct($model)
    {
        $this->model = $model;
    }

    /**
     * Creates a new class instance.
     *
     * @param mixed $model
     *
     * @return TouchedModelRelationsHandler
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
        // #region We filter the componsed relation from default relations to recursively set create model relations
        $composed = array_filter($relations, function ($current) {
            return is_string($current) && (false !== strpos($current, '.'));
        });
        $relations = array_diff($relations, $composed);
        // #endregion We filter the componsed relation from default relations to recursively set create model relations
        foreach ($relations as $relation) {
            if (!(method_exists($this->model, $relation) && !empty($attributes[$relation] ?? []))) {
                continue;
            }
            Arr::isnotassoclist($attributes[$relation] ?? []) ?
                ($batch ?
                    $this->createManyBatch($this->model->$relation(), $attributes[$relation]) :
                    $this->createMany($this->model->$relation(), $attributes[$relation], $this->resolveRelations($composed, $relation))
                ) :
                $this->createOne($this->model->$relation(), $attributes[$relation], $this->resolveRelations($composed, $relation));
        }
    }

    /**
     * Update model child relations based on user provided values.
     *
     * @return void
     */
    public function update(array $relations, array $attributes)
    {
        foreach ($relations as $relation) {
            if (method_exists($this->model, $relation) && !empty($attributes[$relation] ?? [])) {
                $this->updateRelations($this->model->$relation(), $attributes[$relation]);
            }
            continue;
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
        foreach ($relations as $relation) {
            if (!(method_exists($this->model, $relation) && !empty($attributes[$relation] ?? []))) {
                continue;
            }
            $this->refreshRelations($this->model->$relation(), $attributes[$relation]);
        }
    }

    /**
     * @param mixed $nextInstance
     *
     * @throws \LogicException
     *
     * @return mixed
     */
    private static function updateOrCreate($nextInstance, array $value = [])
    {
        if (1 < \count($value)) {
            return $nextInstance->updateOrCreate(
                ...static::formatUpsertAttributes(
                    $nextInstance,
                    $value[0],
                    $value[1]
                )
            );
        }
        if (1 === \count($value)) {
            return $nextInstance->updateOrCreate(
                ...static::formatUpsertAttributes(
                    $nextInstance,
                    $value[0],
                    $value[0]
                )
            );
        }
        throw new \LogicException('Expected ' . __METHOD__ . ' to receive an array of 1 or 2 array values');
    }

    /**
     * @param mixed $nextInstance
     *
     * @return array
     */
    private static function formatCreateManyAttributes($nextInstance, array $attributes = [])
    {
        if ($nextInstance instanceof BelongsToMany) {
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
     * Format attribute before insert action
     * 
     * @param mixed $instance 
     * @param array $attributes 
     * @return array 
     */
    private static function formatCreateAttributes($instance, array $attributes = [])
    {
        return $instance instanceof BelongsToMany ? [Arr::except($attributes, ['pivot', 'joining']), $attributes['pivot'] ?? $attributes['joining'] ?? []] : [$attributes];
    }

    /**
     * Format attribute before upserting
     * 
     * @param mixed $instance 
     * @param array $attributes 
     * @param array $values 
     * @return array 
     */
    private static function formatUpsertAttributes($instance, array $attributes, array $values = [])
    {
        return $instance instanceof BelongsToMany ? [$attributes, Arr::except($values, ['pivot', 'joining']), $values['pivot'] ?? $values['joining'] ?? []] : [$attributes, $values];
    }

    /**
     * @param mixed $instance
     *
     * @throws \LogicException
     *
     * @return void
     */
    private function updateRelations($instance, array $values)
    {
        if (Arr::isassoc($values)) {
            $instance->update($values);
            return;
        }
        foreach (Arr::isnotassoclist($values[0] ?? []) ? $values : [$values] as $value) {
            static::updateOrCreate(clone $instance, $value);
        }
    }

    /**
     * @param mixed $instance
     *
     * @return void
     */
    private function refreshRelations($instance, array $values)
    {
        if (Arr::isnotassoclist($values)) {
            // TODO : Instead of deleting previous relation, find a best implementation
            // that insert new instance and update existing ones
            (clone $instance)->delete();
            (clone $instance)->createMany(...static::formatCreateManyAttributes($instance, $values));
        } else {
            (clone $instance)->delete();
            (clone $instance)->create(...static::formatCreateAttributes($instance, $values));
        }
    }

    private function createMany($instance, array $attributes, array $relations)
    {
        foreach ($attributes as $current) {
            $result = Arr::isnotassoclist($current) ? static::updateOrCreate(clone $instance, $current) : $instance->create(...static::formatCreateAttributes($instance, $current));
            // Recursively execute the create implementation relations attached to the model
            self::new($result)->create($relations, $current);
        }
    }

    /**
     * Insert values in database in batches
     * @param mixed $instance 
     * @param array $attributes 
     * @return void 
     */
    private function createManyBatch($instance, array $attributes)
    {
        $instance->createMany(...static::formatCreateManyAttributes($instance, $attributes));
    }

    /**
     * Insert row into database table
     * 
     * @param mixed $instance 
     * @param array $attributes 
     * @param array $relations 
     * @return void 
     * @throws LogicException 
     */
    private function createOne($instance, array $attributes, array $relations)
    {
        $result = $instance->create(...static::formatCreateAttributes($instance, $attributes));
        // Recursively execute the create implementation relations attached to the model
        self::new($result)->create($relations, $attributes);
    }

    /**
     * Get relations that has the $relation value as parent relation 
     * 
     * @param array $relations 
     * @param mixed $relation 
     * @return array 
     */
    private function resolveRelations(array $relations, string $relation)
    {
        return iterator_to_array($this->findAll($relations, function ($iterator) use ($relation) {
            return "$relation." === substr($iterator, 0, strlen("$relation."));
        }, function ($value) use ($relation) {
            return substr($value, strlen("$relation."));
        }));
    }

    /**
     * Find all values matching user provided callback
     * 
     * @param array $list 
     * @param Closure(T $value, $key):bool $callback 
     * @param Closure(T $value, $key):mixed $callback
     * @return \Traversable<T>
     */
    private function findAll(array $list, \Closure $callback, \Closure $project = null)
    {
        foreach ($list as $key => $value) {
            if ($callback($value, $key)) {
                yield $key => $project ? $project($value) : $value;
            }
        }
    }
}
