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
use Drewlabs\Core\Helpers\Str;
use Drewlabs\Packages\Database\Eloquent\QueryMethod;
use Drewlabs\Packages\Database\TouchedModelRelationsHandler;

trait UpdateQueryLanguage
{
    public function update(...$args)
    {
        return $this->model->getConnection()->transaction(function () use ($args) {
            return $this->overload($args, [
                'updateV1',
                'updateV1_1',
                'updateV2',
                'updateV3',
                'updateV4',
                'updateV5',
            ]);
        });
    }

    private function updateV1(array $query, $attributes, bool $batch = false)
    {
        return $this->updateByQueryCommand($query, $attributes, $batch);
    }

    private function updateV1_1(FiltersInterface $query, $attributes, bool $batch = false)
    {
        return $this->updateByQueryCommand($query, $attributes, $batch);
    }

    private function updateV2(
        int $id,
        $attributes,
        \Closure $callback = null
    ) {
        return $this->updateCommand((string) $id, $attributes, [], $callback);
    }

    private function updateV3(int $id, $attributes, $params, \Closure $callback = null)
    {
        return $this->updateCommand((string) $id, $attributes, $params, $callback);
    }

    private function updateV4(string $id, $attributes, \Closure $callback = null)
    {
        return $this->updateCommand((string) $id, $attributes, [], $callback);
    }

    private function updateV5(string $id, $attributes, $params, \Closure $callback = null)
    {
        return $this->updateCommand($id, $attributes, $params, $callback);
    }

    /**
     * Execute the updated query against the model instance.
     *
     * @param array|FiltersInterface $query
     * @param array                  $attributes
     *
     * @return mixed
     */
    private function updateByQueryCommand(
        $query,
        $attributes,
        bool $batch = false
    ) {
        $attributes = $this->attributesToArray($attributes);

        return $batch ? $this->proxy(
            $this->builderFactory()(drewlabs_core_create_attribute_getter('model', null)($this), $query),
            QueryMethod::UPDATE,
            [$this->parseAttributes(($attributes instanceof Model) ? $attributes->toArray() : $attributes)]
        ) : array_reduce(
            $this->select($query)->all(),
            function ($carry, $value) use ($attributes) {
                $this->proxy(
                    $value,
                    QueryMethod::UPDATE,
                    [$this->parseAttributes(($attributes instanceof Model) ? $attributes->toArray() : $attributes)]
                );
                ++$carry;

                return $carry;
            },
            0
        );
    }

    private function updateCommand($id, $attributes, $params, \Closure $callback = null)
    {
        $callback = $callback ?? static function ($value) {
            return $value;
        };
        $attributes = $this->attributesToArray($attributes);
        // $that = $this;
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
                $this->updateByQueryCommand(['where' => [$model->getPrimaryKey(), $key]], $values);
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
        $params = drewlabs_database_parse_update_handler_params($params);
        $method = $params['method'];
        $upsert = $params['upsert'] ?? true;
        $isComposedMethod = Str::contains($method, '__') && \in_array(Str::split($method, '__')[0], [QueryMethod::UPDATE], true);

        return \is_string($method) && ((null !== ($params['relations'] ?? null)) || $isComposedMethod) ?
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
