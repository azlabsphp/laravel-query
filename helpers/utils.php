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

use Drewlabs\Core\Helpers\Arr;
use Drewlabs\Packages\Database\EloquentQueryBuilderMethods;
use Illuminate\Database\Eloquent\Collection;

if (!function_exists('create_relations_after_create')) {

    /**
     * Call Eloquent model relation {create}/{updateOrCreate} method after the model is inserted.
     *
     * @param mixed $model
     * @param bool  $batch
     *
     * @return void
     */
    function create_relations_after_create($model, array $relations, array $attributes, $batch = false)
    {
        return array_reduce($relations, static function ($model, $relation) use ($attributes, $batch) {
            $inputs = $attributes[$relation] ?? [];
            if (method_exists($model, $relation) && !empty($inputs)) {
                $createMany = static function ($model, $relation, $attributes) use ($batch) {
                    $handleBatch = static function ($model, $relation) use ($attributes) {
                        // There is no need to set the inserted related inputs
                        $model->$relation()->createMany(array_map(
                            static function ($value) {
                                return array_merge(
                                    $value,
                                    [
                                        'created_at' => date('Y-m-d H:i:s'),
                                        'updated_at' => date('Y-m-d H:i:s'),
                                    ]
                                );
                            },
                            $attributes
                        ));

                        return $model;
                    };
                    $handleMap = static function ($model, string $relation) use ($attributes) {
                        return call_user_func([$model, 'setRelation'], $relation, new Collection(
                            array_map(static function ($current) use ($model, $relation) {
                                // When looping through relation values, if the element is an array list
                                // update or create the relation
                                if (drewlabs_core_array_is_no_assoc_array_list($current)) {
                                    return drewlabs_database_update_or_create($model->$relation(), $current);
                                }
                                // else, simply create the entry
                                return $model->$relation()->create($current);
                            }, $attributes)
                        ));
                    };

                    return $batch ? $handleBatch($model, $relation) : $handleMap($model, $relation);
                };
                $createOne = static function ($model, $relation, $attributes) {
                    return call_user_func(
                        [$model, 'setRelation'],
                        $relation,
                        $model->$relation()->create($attributes)
                    );
                };
                return Arr::isnotassoclist($inputs) ? $createMany($model, $relation, $inputs) : $createOne($model, $relation, $inputs);
            }

            return $model;
        }, $model);
    }
}

if (!function_exists('upsert_relations_after_update')) {

    /**
     * Update or create Eloquent model after it was updated.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     *
     * @return void
     */
    function upsert_relations_after_update($model, array $relations, array $attributes, bool $upsert)
    {
        return array_reduce($relations, static function ($model, $relation) use ($attributes, $upsert) {
            $keyValue = $attributes[$relation] ?? [];
            if (method_exists($model, $relation) && !empty($keyValue)) {
                if ($upsert) {
                    Arr::isnotassoclist($keyValue[0] ?? []) ?
                        (static function ($value, string $relation) use ($keyValue) {
                            foreach ($keyValue as $v) {
                                drewlabs_database_update_or_create($value->$relation(), $v);
                            }
                        })($model, $relation) : (static function ($value, string $relation) use ($keyValue) {
                            return drewlabs_database_update_or_create($value->$relation(), $keyValue);
                        })($model, $relation);
                } else {
                    Arr::isnotassoclist($keyValue) ?
                        (static function ($value, $relation) use ($keyValue) {
                            $value->$relation()->delete();
                            $value->$relation()->createMany(
                                array_map(static function ($value) {
                                    return array_merge(
                                        $value,
                                        [
                                            'created_at' => date('Y-m-d H:i:s'),
                                            'updated_at' => date('Y-m-d H:i:s'),
                                        ]
                                    );
                                }, $keyValue)
                            );
                        })($model, $relation) : (static function ($value, $relation) use ($keyValue) {
                            $value->$relation()->delete();
                            $value->$relation()->create($keyValue);
                        })($model, $relation);
                }
            }
            return $model;
        }, $model);
    }
}

if (!function_exists('drewlabs_database_update_or_create')) {

    /**
     * @param \Illuminate\Database\Eloquent\Model $relation
     * @param array                               $value
     *
     * @return mixed
     */
    function drewlabs_database_update_or_create($relation, $value)
    {
        if (2 === count($value)) {
            return $relation->updateOrCreate($value[0], $value[1]);
        }
        if (1 === count($value)) {
            return $relation->updateOrCreate($value[0], $value[0]);
        }
    }
}

if (!function_exists('drewlabs_database_is_dynamic_create_method')) {

    /**
     * Check if a provided method name is a dynamic method starting with [create] or [insert].
     *
     * @return bool
     */
    function drewlabs_database_is_dynamic_create_method(string $method)
    {
        if (!drewlabs_core_strings_contains($method, '__')) {
            return false;
        }
        $method = drewlabs_core_strings_to_array($method, '__')[0];
        // TODO : In future release insert must not be supported
        return in_array(
            $method,
            [
                EloquentQueryBuilderMethods::CREATE,
                EloquentQueryBuilderMethods::INSERT_MANY,
            ],
            true
        );
    }
}

if (!function_exists('drewlabs_database_is_dynamic_update_method')) {

    /**
     * Check if a provided method name is a dynamic method starting with [update].
     *
     * @return bool
     */
    function drewlabs_database_is_dynamic_update_method(string $method)
    {
        if (!drewlabs_core_strings_contains($method, '__')) {
            return false;
        }
        $method = drewlabs_core_strings_to_array($method, '__')[0];

        return in_array($method, [EloquentQueryBuilderMethods::UPDATE], true);
    }
}
