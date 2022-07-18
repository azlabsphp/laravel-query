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

namespace Drewlabs\Packages\Database\Traits;

use Drewlabs\Contracts\Data\Model\Model;
use Drewlabs\Core\Helpers\Arr;
use Drewlabs\Packages\Database\EloquentQueryBuilderMethods;
use Drewlabs\Packages\Database\TouchedModelRelationsHandler;

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
                'updateV6',
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
        ?\Closure $callback = null
    ) {
        return $this->updateByID((string) $id, $attributes, [], $callback);
    }

    public function updateV4(
        int $id,
        array $attributes,
        $params,
        ?\Closure $callback = null
    ) {
        return $this->updateByID((string) $id, $attributes, $params, $callback);
    }

    public function updateV5(
        string $id,
        array $attributes,
        ?\Closure $callback = null
    ) {
        return $this->updateByID((string) $id, $attributes, [], $callback);
    }

    public function updateV6(
        string $id,
        array $attributes,
        $params,
        ?\Closure $callback = null
    ) {
        return $this->updateByID($id, $attributes, $params, $callback);
    }

    private function updateByQuery(
        array $query,
        array $attributes,
        bool $batch = false
    ) {
        if ($batch) {
            return $this->proxy(
                array_reduce(
                    Arr::isnotassoclist($query) ?
                        $query :
                        [$query],
                    static function ($model, $q) {
                        return ModelFiltersHandler($q)->apply($model);
                    },
                    drewlabs_core_create_attribute_getter('model', null)($this)
                ),
                EloquentQueryBuilderMethods::UPDATE,
                [$this->parseAttributes(($attributes instanceof Model) ? $attributes->toArray() : $attributes)]
            );
        } else {
            // Loop through the matching columns and update each
            return array_reduce(
                $this->select($query)->all(),
                function ($carry, $value) use ($attributes) {
                    $this->proxy(
                        $value,
                        EloquentQueryBuilderMethods::UPDATE,
                        [$this->parseAttributes(($attributes instanceof Model) ? $attributes->toArray() : $attributes)]
                    );
                    ++$carry;

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
        ?\Closure $callback = null
    ) {
        $callback = $callback ?? static function ($value) {
            return $value;
        };
        // $that = $this;
        // region Update Handler func
        // TODO : Add an update handler func that update the model
        // The Call the callback passed if one passed in
        $update_model_func = function (
            self $self,
            $key,
            array $values
        ) use ($callback) {
            return function (?\Closure $callable = null) use (
                $self,
                $key,
                $values,
                $callback
            ) {
                $model = drewlabs_core_create_attribute_getter('model', null)($self);
                $this->updateByQuery(['where' => [$model->getPrimaryKey(), $key]], $values);
                // Select the updated model
                $model_ = $this->select($key);
                // If there is a callable, call the callable, passing in updated model first and the other
                // params later
                if ($callable) {
                    $params_ = (\array_slice(\func_get_args(), 1));
                    $params_ = array_merge([$model_], $params_);
                    $result = \call_user_func($callable, ...$params_);
                    $model_ = \is_object($result) ? $result : $model_;
                }
                // Call the outer callback
                return $callback($model_);
            };
        };
        // endregion Update handler fund
        // Parse the params in order to get the method and upsert value
        $params = drewlabs_database_parse_update_handler_params($params);
        $method = $params['method'];
        $upsert = $params['upsert'] ?? true;

        return \is_string($method) && ((null !== ($params['relations'] ?? null)) || drewlabs_database_is_dynamic_update_method($method)) ?
            $update_model_func(
                $this,
                $id,
                $attributes
            )(static function (Model $model) use (
                $attributes,
                $upsert,
                $method,
                $params
            ) {
                return $upsert ? TouchedModelRelationsHandler::new($model)
                    ->update(
                        $params['relations'] ?? \array_slice(drewlabs_database_parse_dynamic_callback($method), 1),
                        $attributes,
                    ) : TouchedModelRelationsHandler::new($model)
                    ->refresh(
                        $params['relations'] ?? \array_slice(drewlabs_database_parse_dynamic_callback($method), 1),
                        $attributes,
                    );
            }) : $update_model_func($this, $id, $attributes)();
    }
}
