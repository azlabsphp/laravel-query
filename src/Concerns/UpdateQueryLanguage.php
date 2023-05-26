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

namespace Drewlabs\Packages\Concerns;

use Closure;
use Drewlabs\Packages\Database\QueryableRelations;
use Drewlabs\Query\Contracts\FiltersInterface;
use Drewlabs\Query\Contracts\TransactionManagerInterface;

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
    private function executeUpdateQuery($query, $attributes, bool $batchMode = false)
    {
        if ($batchMode) {
            return $this->builderFactory()($this->queryable, $query)->update($this->parseAttributes($this->attributesToArray($attributes)));
        }
        return array_reduce($this->select($query)->all(), function ($carry, $builder) use ($attributes) {
            $builder->update($this->parseAttributes($this->attributesToArray($attributes)));
            ++$carry;
            return $carry;
        }, 0);
    }

    private function updateCommand($id, $attributes, array $params, \Closure $callback = null)
    {
        $callback = $callback ?? static function ($value) {
            return $value;
        };
        $attributes = $this->attributesToArray($attributes);
        // Parse the params in order to get the method and upsert value
        $upsert = $params['upsert'] ?? true;

        if (isset($params['relations']) && !empty($params['relations'])) {
            return $this->createUpdateClosure($this, $id, $attributes, $callback)(static function ($model) use ($attributes, $upsert, $params) {
                return $upsert ? QueryableRelations::new($model)->update($params['relations'] ?? [], $attributes) : QueryableRelations::new($model)->refresh($params['relations'] ?? [], $attributes);
            });
        }
        return $this->createUpdateClosure($this, $id, $attributes, $callback)();
    }

    /**
     * Creates a closure that is invoked to update the model
     * 
     * @param UpdateQueryLanguage $self 
     * @param mixed $key 
     * @param array $values 
     * @param Closure $callback 
     * @return Closure(Closure|null $callable = null): mixed 
     */
    private function createUpdateClosure(self $self, $key, array $values, \Closure $callback)
    {
        return function (\Closure $callable = null) use (
            $self,
            $key,
            $values,
            $callback
        ) {
            $this->executeUpdateQuery(['where' => [$self->queryable->getPrimaryKey(), $key]], $values);
            // Select the updated model
            $instance = $this->select($key);
            // If there is a callable, call the callable, passing in updated model first and the other
            // params later
            if ($callable) {
                $params_ = (\array_slice(\func_get_args(), 1));
                $params_ = array_merge([$instance], $params_);
                $result = \call_user_func($callable, ...$params_);
                $instance = \is_object($result) ? $result : $instance;
            }
            // Call the outer callback
            return $callback($instance);
        };
    }
}
