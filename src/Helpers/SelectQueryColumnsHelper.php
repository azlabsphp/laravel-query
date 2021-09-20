<?php

namespace Drewlabs\Packages\Database\Helpers;

class SelectQueryColumnsHelper
{

    /**
     * Convert user provided selectable columns in a tuple of $columns and $relations to load
     * 
     * @param array $values 
     * @param array $model_relations
     * @param array $declared_columns
     * @return array<string[]>
     */
    public static function asTuple($values = ['*'], array $declared_columns = [], array $model_relations = [])
    {
        $relations_ = [];
        $values = $values ?? [];
        // Add top level relations in the list of relations that can be loaded
        foreach ($model_relations ?? [] as $key => $value) {
            if (drewlabs_core_strings_contains($value, '.')) {
                $relations_[$key] = drewlabs_core_strings_before('.', $value);
            }
        }
        // TODO: Get list of relations to be loaded
        $relations = array_filter(
            array_merge($model_relations, $relations_),
            function ($relation) use ($values) {
                return in_array($relation, $values);
            }
        );
        // TODO : Filter $columns removing relations
        $columns_ = array_intersect($values, $declared_columns);
        if (in_array('*', $values)) {
            $columns_ = [];
        } else {
            $columns_ = empty($value = array_diff($columns_, $relations)) ? [null] : $value;
        }
        return [$columns_, $relations];
    }
}
