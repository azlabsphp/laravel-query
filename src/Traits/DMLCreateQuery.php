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

use Closure;
use Drewlabs\Contracts\Data\DataProviderHandlerParamsInterface;
use Drewlabs\Core\Helpers\Str;
use Drewlabs\Packages\Database\EloquentQueryBuilderMethods;
use Drewlabs\Packages\Database\TouchedModelRelationsHandler;
use RuntimeException;

use function Drewlabs\Packages\Database\Proxy\DMLManager;

trait DMLCreateQuery
{
    /**
     * {@inheritDoc}
     */
    public function create(...$args)
    {
        return $this->model->getConnection()->transaction(function () use ($args) {
            return $this->overload($args, [
                'createV1',
                'createV2',
                'createV3',
            ]);
        });
    }

    /**
     * @return Model
     */
    public function createV1(array $attributes, ?\Closure $callback = null)
    {
        $callback = $callback ?: static function ($param) {
            return $param;
        };

        return $callback(
            $this->proxy(
                drewlabs_core_create_attribute_getter('model', null)($this),
                EloquentQueryBuilderMethods::CREATE,
                [$this->parseAttributes($attributes)]
            )
        );
    }

    /**
     * @param mixed $params
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     *
     * @return mixed
     */
    public function createV2(array $attributes, $params, ?\Closure $callback = null)
    {
        if (!(\is_array($params) || ($params instanceof DataProviderHandlerParamsInterface))) {
            throw new \InvalidArgumentException('Argument 2 of the create method must be an array or an instance of ' . DataProviderHandlerParamsInterface::class);
        }

        return $this->handleCreateStatement(
            $attributes,
            drewlabs_database_parse_create_handler_params($params),
            false,
            $callback
        );
    }

    /**
     * @param array|DataProviderHandlerParamsInterface $params
     *
     * @return Model
     */
    public function createV3(array $attributes, $params, bool $batch, ?\Closure $callback = null)
    {
        if (!(\is_array($params) || ($params instanceof DataProviderHandlerParamsInterface))) {
            throw new \InvalidArgumentException('Argument 2 of the create method must be an array or an instance of ' . DataProviderHandlerParamsInterface::class);
        }
        return $this->handleCreateStatement(
            $attributes,
            drewlabs_database_parse_create_handler_params($params),
            $batch,
            $callback
        );
    }

    /**
     * Base method that provides create implementation of the DML manager class
     * 
     * @param array $attributes 
     * @param array $params 
     * @param bool $batch 
     * @param null|Closure $callback 
     * @return mixed 
     * @throws RuntimeException 
     */
    private function handleCreateStatement(array $attributes, array $params, bool $batch = false, ?\Closure $callback = null)
    {
        $callback = $callback ?: static function ($param) {
            return $param;
        };
        $method = $params['method'] ?? EloquentQueryBuilderMethods::CREATE;
        $upsert_conditions = $params['upsert_conditions'] ?: [];
        $upsert = $params['upsert'] && !empty($upsert_conditions) ? true : false;
        $isComposedMethod = Str::contains($method, '__') && in_array(
            Str::split($method, '__')[0],
            [
                EloquentQueryBuilderMethods::CREATE,
                EloquentQueryBuilderMethods::INSERT_MANY,
            ],
            true
        );
        if (\is_string($method) && ((null !== ($params['relations'] ?? null)) || $isComposedMethod)) {
            $relations = $params['relations'] ?? \array_slice(drewlabs_database_parse_dynamic_callback($method), 1) ?? [];
            // TODO: If the current model contains parent relations, create the parent relation
            $instance = drewlabs_core_create_attribute_getter('model', null)($this);
            $attributes = $this->createParentIfExists($instance, $attributes, $relations);
            // To avoid key index issues, we reset the relations array keys if any unset() call
            // was made on the relations variable
            $relations = array_values($relations);
            $instance = $this->proxy(
                $instance,
                $upsert ? EloquentQueryBuilderMethods::UPSERT : EloquentQueryBuilderMethods::CREATE,
                // if Upserting, pass the upsertion condition first else, pass in the attributes
                $upsert ? [$upsert_conditions, $this->parseAttributes($attributes)] : [$this->parseAttributes($attributes)]
            );
            TouchedModelRelationsHandler::new($instance)->create(
                $relations,
                $attributes,
                $batch
            );
            $result = $this->select($instance->getKey(), ['*', ...$relations]);
        } else {
            $result = $this->proxy(
                drewlabs_core_create_attribute_getter('model', null)($this),
                $method,
                // if Upserting, pass the upsertion condition first else, pass in the attributes
                $upsert ? [$upsert_conditions, $this->parseAttributes($attributes)] : [$this->parseAttributes($attributes)]
            );
        }

        return $callback($result);
    }

    /**
     * Implements a clause that insert model parent attributes and set the foreign
     * key back on the model attributes.
     * 
     * It acts like a before create model event listener
     * 
     * @param mixed $instance 
     * @param array $attributes 
     * @param array $relations 
     * @return array 
     */
    private function createParentIfExists($instance, array $attributes, array &$relations)
    {
        for ($i = 0; $i < count($relations); $i++) {
            $value = $relations[$i];
            // If attributes does not contains entry with the current relation name
            // there is not need to process further therefore we simply continues
            if (!isset($attributes[$value]) || empty($attributes[$value])) {
                continue;
            }
            $result = $instance->$value();
            // We escapes relations that are not belongs to relations
            if (!($result instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo)) {
                continue;
            }
            $parent = $result->getRelated();
            $foreignKey = $result->getForeignKeyName();
            // We continue the loop if the Belongs to method call does not return
            // a valid belongs to class for the related model
            if ((null === $parent )|| (null === $foreignKey)) {
                continue;
            }
            $createdInstance = DMLManager($parent)->create($attributes[$value]);
            // Once the create query of the parent model is executed, we add the foreign
            // key to the current instance attributes 
            $attributes[$foreignKey] = $createdInstance->getKey();
            // Remove the relation from the list of relations to create after model
            // gets created
            unset($relations[$i]);
        }

        return $attributes;
    }
}
