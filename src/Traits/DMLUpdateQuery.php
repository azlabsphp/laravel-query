<?php

namespace Drewlabs\Packages\Database\Traits;

use Drewlabs\Contracts\Data\Model\Model;
use Drewlabs\Packages\Database\EloquentQueryBuilderMethodsEnum;
use Drewlabs\Packages\Database\Extensions\CustomQueryCriteria;
use Illuminate\Support\Enumerable;

use function Drewlabs\Packages\Database\Proxy\ModelFiltersHandler;

trait DMLUpdateQuery
{
    public function update(...$args)
    {
        return $this->model->getConnection()->transaction(function () use ($args) {
            return $this->overload($args, [
                'updateV2',
                'updateV3',
                'updateV4',
                'updateV5',
                'updateV6'
            ]);
        });
    }

    public function updateV2(
        array $query,
        array $attributes,
        bool $batch = false
    ) {
        return $this->updateByQuery($query, $attributes, $batch);
    }

    public function updateV3(
        int $id,
        array $attributes,
        \Closure $callback = null
    ) {
        return $this->updateByID((string)$id, $attributes, [], $callback);
    }
    public function updateV4(
        int $id,
        array $attributes,
        $params,
        \Closure $callback = null
    ) {
        return $this->updateByID((string)$id, $attributes, $params, $callback);
    }

    public function updateV5(
        string $id,
        array $attributes,
        \Closure $callback = null
    ) {
        return $this->updateByID((string)$id, $attributes, [], $callback);
    }

    public function updateV6(
        string $id,
        array $attributes,
        $params,
        \Closure $callback = null
    ) {
        return $this->updateByID($id, $attributes, $params, $callback);
    }

    private function updateByQuery(
        array $query,
        array $attributes,
        bool $batch = false
    ) {
        if ($batch) {
            return $this->forwardCallTo(
                array_reduce(
                    drewlabs_core_array_is_no_assoc_array_list($query) ?
                        $query :
                        [$query],
                    function ($model, $q) {
                        return (new CustomQueryCriteria($q))->apply($model);
                    },
                    drewlabs_core_create_attribute_getter('model', null)($this)
                ),
                EloquentQueryBuilderMethodsEnum::UPDATE,
                [$this->parseAttributes(($attributes instanceof Model) ? $attributes->toArray() : $attributes)]
            );
        } else {
            // Select the matching columns
            $collection = $this->selectV3($query, function ($result) {
                return $result->getCollection();
            });
            // Loop through the matching columns and update each
            return array_reduce(
                is_array($collection) ?
                    $collection : ($collection instanceof Enumerable ?
                        $collection->all() : (method_exists($collection, 'all') ?
                            $collection->all() : $collection)),
                function ($carry, $value) use ($attributes) {
                    $this->forwardCallTo(
                        $value,
                        EloquentQueryBuilderMethodsEnum::UPDATE,
                        [$this->parseAttributes(($attributes instanceof Model) ? $attributes->toArray() : $attributes)]
                    );
                    $carry += 1;
                    return $carry;
                },
                0
            );
        }
    }

    private function updateByID(
        $id,
        array $attributes,
        $params,
        \Closure $callback = null
    ) {
        $callback = $callback ?? function ($value) {
            return $value;
        };
        // $that = $this;
        #region Update Handler func
        // TODO : Add an update handler func that update the model
        // The Call the callback passed if one passed in
        $update_model_func = function (
            self $self,
            $key,
            array $values
        ) use ($callback) {
            return function (\Closure $callable = null) use (
                $self,
                $key,
                $values,
                $callback
            ) {
                $model = drewlabs_core_create_attribute_getter('model', null)($self);
                // TODO : Update the model
                $this->forwardCallTo(
                    ModelFiltersHandler([
                        'where' => [$model->getPrimaryKey(), $key]
                    ])->apply($model),
                    EloquentQueryBuilderMethodsEnum::UPDATE,
                    [$this->parseAttributes($values)]
                );
                // Select the updated model
                $model_ = $this->select($key);
                // If their is a callable, call the callable, passing in updated model first and the other
                // params later
                if ($callable) {
                    $params_ = (array_slice(func_get_args(), 1));
                    $params_ = array_merge([$model_], $params_);
                    $result = call_user_func($callable, ...$params_);
                    $model_ = is_object($result) ? $result : $model_;
                }
                // Call the outer callback
                return $callback($model_);
            };
        };
        # endregion Update handler fund
        // Parse the params in order to get the method and upsert value
        $params = drewlabs_database_parse_update_handler_params($params);
        $method = $params['method'];
        $upsert = $params['upsert'] ?? false;
        return is_string($method) && drewlabs_database_is_dynamic_update_method($method) ?
            $update_model_func(
                $this,
                $id,
                $attributes
            )(function (Model $model) use (
                $attributes,
                $upsert,
                $method
            ) {
                return drewlabs_database_upsert_relations_after_create(
                    $model,
                    array_slice(drewlabs_database_parse_dynamic_callback($method), 1),
                    $attributes,
                    $upsert
                );
            }) : $update_model_func($this, $id, $attributes)();
    }
}
