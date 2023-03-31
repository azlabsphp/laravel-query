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

namespace Drewlabs\Packages\Database\Query\Concerns;

use Drewlabs\Contracts\Data\Filters\FiltersInterface;
use Drewlabs\Contracts\Data\Model\Model;
use Drewlabs\Packages\Database\Contracts\TransactionManagerInterface;
use Drewlabs\Packages\Database\Eloquent\QueryMethod;
use Drewlabs\Packages\Database\TouchedModelRelationsHandler;

/**
 * @property TransactionManagerInterface transactions
 */
trait UpdateQueryLanguage
{
    public function update(...$args)
    {
        return $this->transactions->transaction(function () use ($args) {
            return $this->overload($args, [
                function (array $query, $attributes, bool $batch = false) {
                    return $this->executeUpdateQuery($query, $attributes, $batch);
                },
                function (FiltersInterface $query, $attributes, bool $batch = false) {
                    return $this->executeUpdateQuery($query, $attributes, $batch);
                },
                function (int $id, $attributes, \Closure $callback = null) {
                    return $this->updateCommand((string) $id, $attributes, [], $callback);
                },
                function (int $id, $attributes, array $params, \Closure $callback = null) {
                    return $this->updateCommand((string) $id, $attributes, $params, $callback);
                },
                function (string $id, $attributes, \Closure $callback = null) {
                    return $this->updateCommand((string) $id, $attributes, [], $callback);
                },
                function (string $id, $attributes, array $params, \Closure $callback = null) {
                    return $this->updateCommand($id, $attributes, $params, $callback);
                },
            ]);
        });
    }

    /**
     * Execute the updated query against the model instance.
     *
     * @param array|FiltersInterface $query
     * @param array                  $attributes
     *
     * @return mixed
     */
    private function executeUpdateQuery($query, $attributes, bool $batch = false)
    {
        $attributes = $this->attributesToArray($attributes);
        return $batch ? $this->proxy(
            $this->builderFactory()(drewlabs_core_create_attribute_getter('model', null)($this), $query),
            QueryMethod::UPDATE,
            [$this->parseAttributes(($attributes instanceof Model) ? $attributes->toArray() : $attributes)]
        ) : array_reduce(
            $this->select($query)->all(),
            function ($carry, $value) use ($attributes) {
                $this->proxy($value, QueryMethod::UPDATE, [$this->parseAttributes(($attributes instanceof Model) ? $attributes->toArray() : $attributes)]);
                ++$carry;
                return $carry;
            },
            0
        );
    }

    private function updateCommand($id, $attributes, array $params, \Closure $callback = null)
    {
        $callback = $callback ?? static function ($value) {
            return $value;
        };
        $attributes = $this->attributesToArray($attributes);
        // region Update Handler func
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
                $this->executeUpdateQuery(['where' => [$model->getPrimaryKey(), $key]], $values);
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
        // endregion update handler fund
        // Parse the params in order to get the method and upsert value
        $upsert = $params['upsert'] ?? true;

        return isset($params['relations']) ?
            $update_model_func($this, $id, $attributes)(static function (Model $model) use ($attributes, $upsert, $params) {
                return $upsert ? TouchedModelRelationsHandler::new($model)->update($params['relations'] ?? [], $attributes) :
                    TouchedModelRelationsHandler::new($model)->refresh($params['relations'] ?? [], $attributes);
            }) : $update_model_func($this, $id, $attributes)();
    }
}
