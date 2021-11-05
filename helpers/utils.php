<?php

use Drewlabs\Packages\Database\EloquentQueryBuilderMethodsEnum;
use Illuminate\Database\Eloquent\Collection;

if (!function_exists('create_relations_after_create')) {

    /**
     * Call Eloquent model relation {create}/{updateOrCreate} method after the model is inserted
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param array $relations
     * @param array $attributes
     * @param boolean $batch
     * @return void
     */
    function create_relations_after_create($model, array $relations, array $attributes, $batch = false)
    {
        return array_reduce($relations, function ($model, $relation) use ($attributes, $batch) {
            $keyValue = $attributes[$relation] ?? [];
            if (method_exists($model, $relation) && !empty($keyValue)) {
                $createMany = function ($model, $relation) use ($keyValue, $batch) {
                    $handlBatch =  function ($model, $relation) use ($keyValue) {
                        // There is no need to set the inserted related inputs
                        $model->{$relation}()->createMany(array_map(
                            function ($value) {
                                return array_merge(
                                    $value,
                                    [
                                        'created_at' => date('Y-m-d H:i:s'),
                                        'updated_at' => date('Y-m-d H:i:s')
                                    ]
                                );
                            },
                            $keyValue
                        ));
                        return $model;
                    };
                    $handleMap = function ($model, string $relation) use ($keyValue) {
                        return call_user_func([$model, 'setRelation'], $relation, new Collection(
                            array_map(function ($k) use ($model, $relation) {
                                // When looping through relation values, if the element is an array list
                                // update or create the relation
                                if (drewlabs_core_array_is_no_assoc_array_list($k)) {
                                    return drewlabs_database_update_or_create($model->{$relation}(), $k);
                                } else {
                                    // else, simply create the entry
                                    return $model->{$relation}()->create($k);
                                }
                            }, $keyValue)
                        ));
                    };
                    return $batch ? $handlBatch($model, $relation) : $handleMap($model, $relation);
                };
                $createOne = function ($model, $relation) use ($keyValue) {
                    return call_user_func(
                        [$model, 'setRelation'],
                        $relation,
                        $model->{$relation}()->create($keyValue)
                    );
                };
                return drewlabs_core_array_is_no_assoc_array_list($keyValue) ?
                    $createMany($model, $relation) :
                    $createOne($model, $relation);
            }
            return $model;
        }, $model);
    }
}


if (!function_exists('drewlabs_database_upsert_relations_after_create')) {

    /**
     *
     * Update or create Eloquent model after it was updated
     * 
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param array $relations
     * @param array $attributes
     * @param boolean $upsert
     * @param boolean $batch
     * @return void
     */
    function drewlabs_database_upsert_relations_after_create($model, array $relations, array $attributes, bool $upsert)
    {
        return array_reduce($relations, function ($model, $relation) use ($attributes, $upsert) {
            $keyValue = $attributes[$relation] ?? [];
            if (method_exists($model, $relation) && !empty($keyValue)) {
                if ($upsert) {
                    drewlabs_core_array_is_no_assoc_array_list($keyValue[0] ?? []) ?
                        (function ($model, string $relation) use ($keyValue) {
                            foreach ($keyValue as $v) {
                                drewlabs_database_update_or_create($model->{$relation}(), $v);
                            }
                        })($model, $relation) : (function ($model, string $relation) use ($keyValue) {
                            return drewlabs_database_update_or_create($model->{$relation}(), $keyValue);
                        })($model, $relation);
                } else {
                    drewlabs_core_array_is_no_assoc_array_list($keyValue) ?
                        (function ($model, $relation) use ($keyValue) {
                            $model->{$relation}()->delete();
                            // Create many after deleting the all the related
                            $model->{$relation}()->createMany(
                                array_map(function ($value) {
                                    return array_merge(
                                        $value,
                                        [
                                            'created_at' => date('Y-m-d H:i:s'),
                                            'updated_at' => date('Y-m-d H:i:s')
                                        ]
                                    );
                                }, $keyValue)
                            );
                        })($model, $relation) : (function ($model, $relation) use ($keyValue) {
                            $model->{$relation}()->delete();
                            $model->{$relation}()->create($keyValue);
                        })($model, $relation);
                }
            }
        }, $model);
    }
}

if (!function_exists('drewlabs_database_update_or_create')) {

    /**
     *
     * @param \Illuminate\Database\Eloquent\Model $relation
     * @param array $value
     * @return mixed
     */
    function drewlabs_database_update_or_create($relation, $value)
    {
        if (count($value) === 2) {
            return $relation->updateOrCreate($value[0], $value[1]);
        }
        if (count($value) === 1) {
            return $relation->updateOrCreate($value[0], $value[0]);
        }
    }
}


if (!function_exists('drewlabs_database_is_dynamic_create_method')) {

    /**
     * Check if a provided method name is a dynamic method starting with [create] or [insert]
     *
     * @param string $method
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
                EloquentQueryBuilderMethodsEnum::CREATE,
                EloquentQueryBuilderMethodsEnum::INSERT_MANY
            ]
        );
    }
}

if (!function_exists('drewlabs_database_is_dynamic_update_method')) {

    /**
     * Check if a provided method name is a dynamic method starting with [update]
     *
     * @param string $method
     * @return bool
     */
    function drewlabs_database_is_dynamic_update_method(string $method)
    {
        if (!drewlabs_core_strings_contains($method, '__')) {
            return false;
        }
        $method = drewlabs_core_strings_to_array($method, '__')[0];
        // TODO : In future release insert must not be supported
        return in_array($method, [EloquentQueryBuilderMethodsEnum::UPDATE]);
    }
}
