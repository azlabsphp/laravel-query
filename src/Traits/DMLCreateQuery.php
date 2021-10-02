<?php

namespace Drewlabs\Packages\Database\Traits;

use Drewlabs\Packages\Database\EloquentQueryBuilderMethodsEnum;
use Drewlabs\Contracts\Data\DataProviderHandlerParamsInterface;
use InvalidArgumentException;

trait DMLCreateQuery
{

    /**
     * @inheritDoc
     */
    public function create(...$args)
    {
        return $this->model->getConnection()->transaction(function () use ($args) {
            return $this->overload($args, [
                'createV1',
                'createV2',
                'createV3'
            ]);
        });
    }

    /**
     * @param array $attributes
     * @param \Closure|null $callback
     * @return Model
     */
    public function createV1(array $attributes, \Closure $callback = null)
    {
        $callback = $callback ? $callback : function ($param) {
            return $param;
        };
        return $callback(
            $this->forwardCallTo(
                drewlabs_core_create_attribute_getter('model', null)($this),
                'add',
                [$this->parseAttributes($attributes)]
            )
        );
    }

    /**
     *
     * @param array $attributes
     * @param array|DataProviderHandlerParamsInterface $params
     * @param \Closure|null $callback
     * @return Model
     */
    public function createV2(array $attributes, $params, \Closure $callback = null)
    {
        if (!(is_array($params) || ($params instanceof DataProviderHandlerParamsInterface))) {
            throw new InvalidArgumentException('Argument 2 of the create method must be an array or an instance of ' . DataProviderHandlerParamsInterface::class);
        }
        return $this->handleCreateStatement(
            $attributes,
            drewlabs_database_parse_create_handler_params($params),
            false,
            $callback
        );
    }

    /**
     *
     * @param array $attributes
     * @param array|DataProviderHandlerParamsInterface $params
     * @param bool $batch
     * @param \Closure|null $callback
     * @return Model
     */
    public function createV3(array $attributes, $params, bool $batch, \Closure $callback = null)
    {
        if (!(is_array($params) || ($params instanceof DataProviderHandlerParamsInterface))) {
            throw new InvalidArgumentException('Argument 2 of the create method must be an array or an instance of ' . DataProviderHandlerParamsInterface::class);
        }
        return $this->handleCreateStatement(
            $attributes,
            drewlabs_database_parse_create_handler_params($params),
            $batch,
            $callback
        );
    }

    private function handleCreateStatement(array $attributes, array $params, bool $batch = false, \Closure $callback = null)
    {
        $callback = $callback ? $callback : function ($param) {
            return $param;
        };
        $method = $params['method'] ?? EloquentQueryBuilderMethodsEnum::CREATE;
        $upsert_conditions = $params['upsert_conditions'] ? $params['upsert_conditions'] : [];
        $upsert = $params['upsert'] && !empty($upsert_conditions) ? true : false;
        if (is_string($method) && \drewlabs_database_is_dynamic_create_method($method)) {
            $result = create_relations_after_create(
                $this->forwardCallTo(
                    drewlabs_core_create_attribute_getter('model', null)($this),
                    $upsert ? EloquentQueryBuilderMethodsEnum::UPSERT : EloquentQueryBuilderMethodsEnum::CREATE,
                    // if Upserting, pass the upsertion condition first else, pass in the attributes
                    $upsert ? [$upsert_conditions, $attributes] : [$this->parseAttributes($attributes)]
                ),
                array_slice(drewlabs_database_parse_dynamic_callback($method), 1),
                $attributes,
                $batch
            );
        } else {
            $result = $this->forwardCallTo(
                drewlabs_core_create_attribute_getter('model', null)($this),
                $method,
                // if Upserting, pass the upsertion condition first else, pass in the attributes
                $upsert ? [$upsert_conditions, $attributes] : [$this->parseAttributes($attributes)]
            );
        }
        return $callback($result);
    }
}