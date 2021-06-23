<?php

use Drewlabs\Packages\Database\EloquentQueryBuilderMethodsEnum;
use Illuminate\Database\Eloquent\Collection;

if (!function_exists('create_relations_after_create')) {

    /**
     * Undocumented function
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param array $relations
     * @param array $attributes
     * @param boolean $bulkstatement
     * @return void
     */
    function create_relations_after_create($model, array $relations, array $attributes, $bulkstatement = false)
    {
        return array_reduce($relations, function ($model, $relation) use ($attributes, $bulkstatement) {
            if (method_exists($model, $relation) && array_key_exists($relation, $attributes)  && isset($attributes[$relation])) {
                $isArrayList = \array_filter($attributes[$relation], 'is_array') === $attributes[$relation];
                if ($isArrayList) {
                    if ($bulkstatement) {
                        // There is no need to set the inserted related inputs
                        $model->{$relation}()->createMany(array_map(function ($value) {
                            return array_merge(
                                $value,
                                array(
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'updated_at' => date('Y-m-d H:i:s')
                                )
                            );
                        }, $attributes[$relation]));
                    } else {
                        call_user_func([$model, 'setRelation'], $relation, new Collection(array_map(function ($k) use ($model, $relation) {
                            return $model->{$relation}()->create($k);
                        }, $attributes[$relation])));
                    }
                } else {
                    call_user_func([$model, 'setRelation'], $relation, $model->{$relation}()->create($attributes[$relation]));
                }
            }
            return $model;
        }, $model);
    }
}


if (!function_exists('drewlabs_database_upsert_relations_after_create')) {

    /**
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param array $relations
     * @param array $attributes
     * @param boolean $upsert
     * @param boolean $bulkstatement
     * @return void
     */
    function drewlabs_database_upsert_relations_after_create($model, array $relations, array $attributes, bool $upsert)
    {
        return array_reduce($relations, function ($model, $relation) use ($attributes, $upsert) {
            if (method_exists($model, $relation) && array_key_exists($relation, $attributes) && isset($attributes[$relation])) {
                if ($upsert) {
                    $isArrayList = isset($attributes[$relation][0]) && drewlabs_core_array_is_no_assoc_array_list($attributes[$relation][0]);
                    if ($isArrayList) {
                        foreach ($attributes[$relation] as $v) {
                            drewlabs_database_update_or_create_if_condition_match($model->{$relation}(), $v);
                        }
                    } else {
                        drewlabs_database_update_or_create_if_condition_match($model->{$relation}(), $attributes[$relation]);
                    }
                } else {
                    $isArrayList = isset($attributes[$relation]) && drewlabs_core_array_is_no_assoc_array_list($attributes[$relation]);
                    if ($isArrayList) {
                        $model->{$relation}()->delete();
                        // Create many after deleting the all the related
                        $model->{$relation}()->createMany(array_map(function ($value) use ($model) {
                            return array_merge(
                                $value,
                                array(
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'updated_at' => date('Y-m-d H:i:s')
                                )
                            );
                        }, $attributes[$relation]));
                    } else {
                        $model->{$relation}()->delete();
                        $model->{$relation}()->create($attributes[$relation]);
                    }
                }
            }
        }, $model);
    }
}

if (!function_exists('drewlabs_database_update_or_create_if_condition_match')) {

    /**
     *
     * @param \Illuminate\Database\Eloquent\Model $relation
     * @param array $value
     * @return void
     */
    function drewlabs_database_update_or_create_if_condition_match($relation, $value)
    {
        if (count($value) === 2) {
            $relation->updateOrCreate($value[0], $value[1]);
        }
        if (count($value) === 1) {
            $relation->updateOrCreate($value[0], $value[0]);
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
        return in_array($method, [EloquentQueryBuilderMethodsEnum::CREATE, EloquentQueryBuilderMethodsEnum::INSERT_MANY]);
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