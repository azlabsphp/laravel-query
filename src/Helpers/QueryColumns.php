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

namespace Drewlabs\Packages\Database\Helpers;

use Drewlabs\Core\Helpers\Str;

class QueryColumns
{
    /**
     * Convert user provided selectable columns in a tuple of $columns and $relations to load.
     *
     * @param array $values
     *
     * @return array<string[]>
     */
    public static function asTuple($values = ['*'], array $declared_columns = [], array $model_relations = [])
    {
        $values = $values ?? [];

        // Get the list of top level declared relations
        $top_level_relations = array_map(function ($relation) {
            return Str::contains($relation, '.') ? Str::before('.', $relation) : $relation;
        }, $model_relations ?? []);
        // Creates the list of relation fields to be added to the model list of columns
        $relations = array_filter($values, function($relation) use ($top_level_relations, $model_relations) {
            if (Str::contains($relation, '.')) {
                return in_array(Str::before('.', $relation), $top_level_relations) || in_array($relation, $model_relations);
            }
            return in_array($relation, $top_level_relations);
        });
        // Create the actual list of model column to be selected from the database
        $columns = array_intersect($values, $declared_columns);
        if (\in_array('*', $values, true)) {
            $columns = [];
        } else {
            $columns = empty($value = array_diff($columns, $relations)) ? [null] : $value;
        }
        // Return the tuple of column and relations
        return [$columns, $relations];
    }
}
