<?php

namespace Drewlabs\Packages\Database;

use Drewlabs\Packages\Database\Contracts\TransactionUtils;
use Drewlabs\Contracts\Data\Repository\ModelRepository;
use ReflectionFunction;

class DynamicCRUDQueryHandler
{
    /**
     *
     * @var TransactionUtils
     */
    public $transactionHandler;

    /**
     *
     * @var ModelRepository
     */
    public $repository;

    /**
     * @return static
     */
    public function bindTransactionHandler(TransactionUtils $hanlder)
    {
        $illuminateContainerClazz = "Illuminate\\Container\\Container";
        $hanlder = $hanlder ?? (class_exists($illuminateContainerClazz) ?
            forward_static_call([$illuminateContainerClazz, 'getInstance'])
            ->get(TransactionUtils::class) : null);
        return drewlabs_core_create_attribute_setter('transactionHandler', $hanlder)($this);
    }

    /**
     * @return static
     */
    public function bindRepository(ModelRepository $repository)
    {
        return drewlabs_core_create_attribute_setter('repository', $repository)($this);
    }

    /**
     * Provide functionnalities for inserting a model with it related
     *
     * @param string[] $relations
     * @param array $values
     * @param boolean $parse_inputs
     * @param boolean $upsert
     * @param array $conditions
     * @param boolean $mass_insert
     * @return mixed
     */
    public function create($relations, $values, $parse_inputs = false, $upsert = false, $conditions = [], $mass_insert = true)
    {
        if (is_null($this->repository) || !($this->repository instanceof ModelRepository)) {
            throw new \RuntimeException('Calling ' . __METHOD__ . ' requires binding the repository first. Call bindRepository($repository) method before calling this method');
        }
        return $this->runTransaction(function () use ($relations, $values, $parse_inputs, $upsert, $conditions, $mass_insert) {
            $model = $this->repository->insert($values, $parse_inputs, $upsert, $conditions);
            // Loop through model transactions
            $relations = array_filter($relations ?? [], function ($relation) {
                return is_string($relation);
            });
            foreach ($relations as $i) {
                if (!(method_exists($model, $i) && array_key_exists($i, $values)  && isset($values[$i]))) {
                    continue;
                }
                $isArrayList = \array_filter($values[$i], 'is_array') === $values[$i];
                $insertAllFunc = function () use ($model, $i, $values, $mass_insert) {
                    $batchInsertFunc = function () use ($model, $i, $values) {
                        // TODO : Delete existing model relations and create new ones
                        $model->{$i}()->createMany(array_map(function ($value) {
                            return array_merge(
                                $value,
                                array(
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'updated_at' => date('Y-m-d H:i:s')
                                )
                            );
                        }, $values[$i]));
                    };
                    $loopInsertFunc = function () use ($model, $i, $values) {
                        // TODO : Delete existing model relations and create new ones
                        foreach ($values[$i] as $k) {
                            $model->{$i}()->create($k);
                        }
                    };
                    $mass_insert ? $batchInsertFunc() : $loopInsertFunc();
                };
                $insertOneFunc = function () use ($model, $i, $values) {
                    $model->{$i}()->delete();
                    $model->{$i}()->create($values[$i]);
                };
                $isArrayList ? $insertAllFunc() : $insertOneFunc();
            }
            return $model;
        });
    }

    private function runTransaction(\Closure $callback)
    {
        // Start the transaction
        if (isset($this->transactionHandler)) {
            $this->transactionHandler->startTransaction();
        }
        try {
            // Run the transaction
            $callbackResult = (new ReflectionFunction($callback))->invoke();
            // Return the result of the transaction
            return $this->afterTransaction(function () use ($callbackResult) {
                return $callbackResult;
            });
        } catch (\Exception $e) {
            return $this->afterCancelTransaction(function () use ($e) {
                throw new \RuntimeException($e);
            });
        }
    }

    private function afterTransaction(\Closure $callback)
    {
        if (isset($this->transactionHandler)) {
            $this->transactionHandler->completeTransaction();
        }
        return (new ReflectionFunction($callback))->invoke();
    }

    private function afterCancelTransaction(\Closure $callback)
    {
        if (isset($this->transactionHandler)) {
            $this->transactionHandler->cancel();
        }
        return (new ReflectionFunction($callback))->invoke();
    }



    /**
     * Provides functionnalities for updating a model with it related entries. Note, Only update using model
     * primary key is supported.
     *
     * @param string[] $relations
     * @param int|string $id
     * @param array $values
     * @param boolean $parse_inputs
     * @param boolean|null $upsert
     * @return int
     */
    public function update($relations, $id, $values, $parse_inputs = false, $upsert = true)
    {
        if (is_null($this->repository) || !($this->repository instanceof ModelRepository)) {
            throw new \RuntimeException('Calling ' . __METHOD__ . ' requires binding the repository first. Call bindRepository($repository) method before calling this method');
        }

        return $this->runTransaction(function () use ($relations, $id, $values, $parse_inputs, $upsert) {
            $updated = 0;
            $updated = $this->repository->updateById($id, $values, $parse_inputs);
            $model = $this->repository->findById($id, array($this->repository->{'modelPrimaryKey'}()));

            // If the model is not found return 0 as result
            if (null === $model) return $updated;

            // Else loop through the model relations and update them
            foreach ($relations as $i) {
                if (!(method_exists($model, $i) && array_key_exists($i, $values) && isset($values[$i]))) continue;
                $updateOrInsertFunc = function () use ($i, $values, $model) {
                    $isArrayList = isset($values[$i][0]) && \array_filter($values[$i][0], 'is_array') === $values[$i][0];
                    $updateOrInsertArrayList = function ()  use ($i, $values, $model) {
                        foreach ($values[$i] as $v) {
                            $this->updateOrCreateIfMatchCondition($model->{$i}(), $v);
                        }
                    };
                    $isArrayList ? $updateOrInsertArrayList() : $this->updateOrCreateIfMatchCondition($model->{$i}(), $values[$i]);
                };
                $insertFunc = function () use ($i, $values, $model) {
                    $isArrayList = isset($values[$i]) && \array_filter($values[$i], 'is_array') === $values[$i];
                    $insertArrayAllFunc = function () use ($i, $values, $model) {
                        $model->{$i}()->delete();
                        // Create many after deleting the all the related
                        $model->{$i}()->createMany(array_map(function ($value){
                            return array_merge(
                                $value,
                                array(
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'updated_at' => date('Y-m-d H:i:s')
                                )
                            );
                        }, $values[$i]));
                    };
                    $insertOneFunc = function () use ($i, $values, $model) {
                        $model->{$i}()->delete();
                        $model->{$i}()->create($values[$i]);
                    };
                    $isArrayList ? $insertArrayAllFunc() : $insertOneFunc();
                };
                $upsert ? $updateOrInsertFunc() : $insertFunc();
            }
            return $updated;
        });
    }

    private function updateOrCreateIfMatchCondition($relation, $value)
    {
        if (count($value) === 2) {
            $relation->updateOrCreate($value[0], $value[1]);
        }
        if (count($value) === 1) {
            $relation->updateOrCreate($value[0], $value[0]);
        }
    }
}
