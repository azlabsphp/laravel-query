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

use Drewlabs\Core\Helpers\Arr;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
        foreach ($relations as $relation) {
            if (!(method_exists($this->model, $relation) && !empty($attributes[$relation] ?? []))) {
                continue;
            }
            Arr::isnotassoclist($attributes[$relation] ?? []) ?
                (
                    $batch ?
                    $this->createManyBatch($this->model->$relation(), $attributes[$relation]) :
                    $this->createMany($this->model->$relation(), $attributes[$relation])
                ) :
                $this->createOne($this->model->$relation(), $attributes[$relation]);
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
            if (!(method_exists($this->model, $relation) && !empty($attributes[$relation] ?? []))) {
                continue;
            }
            $this->updateRelations($this->model->$relation(), $attributes[$relation]);
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
        throw new \LogicException('Expected '.__METHOD__.' to receive an array of 1 or 2 array values');
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
     * @param mixed $nextInstance
     *
     * @return array
     */
    private static function formatCreateAttributes($nextInstance, array $attributes = [])
    {
        if ($nextInstance instanceof BelongsToMany) {
            $pivot = $attributes['pivot'] ?? $attributes['joining'] ?? [];
            $attribute = Arr::except($attributes, ['pivot', 'joining']);

            return [$attribute, $pivot];
        }

        return [$attributes];
    }

    /**
     * @param mixed $nextInstance
     *
     * @return array
     */
    private static function formatUpsertAttributes($nextInstance, array $attributes, array $values = [])
    {
        if ($nextInstance instanceof BelongsToMany) {
            $pivot = $values['pivot'] ?? $values['joining'] ?? [];
            $attribute = Arr::except($values, ['pivot', 'joining']);

            return [$attributes, $attribute, $pivot];
        }

        return [$attributes, $values];
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
            // We return after calling update on the child model
            return;
        }
        $values = Arr::isnotassoclist($values[0] ?? []) ? $values : [$values];
        foreach ($values as $value) {
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

    private function createMany($instance, array $attributes)
    {
        return new Collection(
            array_map(static function ($current) use ($instance) {
                // When looping through relation values, if the element is an array list
                // update or create the relation
                return Arr::isnotassoclist($current) ?
                    static::updateOrCreate(clone $instance, $current) : (clone $instance)->create(...static::formatCreateAttributes($instance, $current));
            }, $attributes)
        );
    }

    private function createManyBatch($instance, array $attributes)
    {
        (clone $instance)->createMany(...static::formatCreateManyAttributes($instance, $attributes));
    }

    private function createOne($instance, array $attributes)
    {
        (clone $instance)->create(...static::formatCreateAttributes($instance, $attributes));
    }
}
