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

namespace Drewlabs\Packages\Database;

use Drewlabs\Contracts\Data\Repository\ModelRepository;
use Drewlabs\Packages\Database\Contracts\TransactionUtils;
use Drewlabs\Packages\Database\Traits\HasIocContainer;

class DynamicCRUDQueryHandler
{
    use HasIocContainer;
    /**
     * @var TransactionUtils
     */
    public $transactionHandler;

    /**
     * @var ModelRepository
     */
    public $repository;

    /**
     * @return static
     */
    public function bindTransactionHandler(TransactionUtils $hanlder)
    {
        $hanlder = $hanlder ?? self::createResolver(TransactionUtils::class)();

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
     * Provide functionnalities for inserting a model with it related.
     *
     * @param string[] $relations
     * @param array    $values
     * @param bool     $parse_inputs
     * @param bool     $upsert
     * @param array    $conditions
     * @param bool     $mass_insert
     *
     * @return mixed
     */
    public function create($relations, $values, $parse_inputs = false, $upsert = false, $conditions = [], $mass_insert = true)
    {
        if (null === $this->repository || !($this->repository instanceof ModelRepository)) {
            throw new \RuntimeException('Calling '.__METHOD__.' requires binding the repository first. Call bindRepository($repository) method before calling this method');
        }

        return $this->runTransaction(function () use ($relations, $values, $parse_inputs, $upsert, $conditions, $mass_insert) {
            $model = $this->repository->insert($values, $parse_inputs, $upsert, $conditions);
            // Loop through model transactions
            $relations = array_filter($relations ?? [], static function ($relation) {
                return \is_string($relation);
            });
            foreach ($relations as $i) {
                if (!(method_exists($model, $i) && \array_key_exists($i, $values) && isset($values[$i]))) {
                    continue;
                }
                $isArrayList = array_filter($values[$i], 'is_array') === $values[$i];
                $insertAllFunc = static function () use ($model, $i, $values, $mass_insert) {
                    $batchInsertFunc = static function () use ($model, $i, $values) {
                        // TODO : Delete existing model relations and create new ones
                        $model->{$i}()->createMany(array_map(static function ($value) {
                            return array_merge(
                                $value,
                                [
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'updated_at' => date('Y-m-d H:i:s'),
                                ]
                            );
                        }, $values[$i]));
                    };
                    $loopInsertFunc = static function () use ($model, $i, $values) {
                        // TODO : Delete existing model relations and create new ones
                        foreach ($values[$i] as $k) {
                            $model->{$i}()->create($k);
                        }
                    };
                    $mass_insert ? $batchInsertFunc() : $loopInsertFunc();
                };
                $insertOneFunc = static function () use ($model, $i, $values) {
                    $model->{$i}()->delete();
                    $model->{$i}()->create($values[$i]);
                };
                $isArrayList ? $insertAllFunc() : $insertOneFunc();
            }

            return $model;
        });
    }

    /**
     * Provides functionnalities for updating a model with it related entries. Note, Only update using model
     * primary key is supported.
     *
     * @param string[]   $relations
     * @param int|string $id
     * @param array      $values
     * @param bool       $parse_inputs
     * @param bool|null  $upsert
     *
     * @return int
     */
    public function update($relations, $id, $values, $parse_inputs = false, $upsert = true)
    {
        if (null === $this->repository || !($this->repository instanceof ModelRepository)) {
            throw new \RuntimeException('Calling '.__METHOD__.' requires binding the repository first. Call bindRepository($repository) method before calling this method');
        }

        return $this->runTransaction(function () use ($relations, $id, $values, $parse_inputs, $upsert) {
            $updated = 0;
            $updated = $this->repository->updateById($id, $values, $parse_inputs);
            $model = $this->repository->findById($id, [$this->repository->{'modelPrimaryKey'}()]);

            // If the model is not found return 0 as result
            if (null === $model) {
                return $updated;
            }

            // Else loop through the model relations and update them
            foreach ($relations as $i) {
                if (!(method_exists($model, $i) && \array_key_exists($i, $values) && isset($values[$i]))) {
                    continue;
                }
                $updateOrInsertFunc = function () use ($i, $values, $model) {
                    $isArrayList = isset($values[$i][0]) && array_filter($values[$i][0], 'is_array') === $values[$i][0];
                    $updateOrInsertArrayList = function () use ($i, $values, $model) {
                        foreach ($values[$i] as $v) {
                            $this->updateOrCreateIfMatchCondition($model->{$i}(), $v);
                        }
                    };
                    $isArrayList ? $updateOrInsertArrayList() : $this->updateOrCreateIfMatchCondition($model->{$i}(), $values[$i]);
                };
                $insertFunc = static function () use ($i, $values, $model) {
                    $isArrayList = isset($values[$i]) && array_filter($values[$i], 'is_array') === $values[$i];
                    $insertArrayAllFunc = static function () use ($i, $values, $model) {
                        $model->{$i}()->delete();
                        // Create many after deleting the all the related
                        $model->{$i}()->createMany(array_map(static function ($value) {
                            return array_merge(
                                $value,
                                [
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'updated_at' => date('Y-m-d H:i:s'),
                                ]
                            );
                        }, $values[$i]));
                    };
                    $insertOneFunc = static function () use ($i, $values, $model) {
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

    private function runTransaction(\Closure $callback)
    {
        // Start the transaction
        if (isset($this->transactionHandler)) {
            $this->transactionHandler->startTransaction();
        }
        try {
            // Run the transaction
            $callbackResult = (new \ReflectionFunction($callback))->invoke();
            // Return the result of the transaction
            return $this->afterTransaction(static function () use ($callbackResult) {
                return $callbackResult;
            });
        } catch (\Exception $e) {
            return $this->afterCancelTransaction(static function () use ($e) {
                throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
            });
        }
    }

    private function afterTransaction(\Closure $callback)
    {
        if (isset($this->transactionHandler)) {
            $this->transactionHandler->completeTransaction();
        }

        return (new \ReflectionFunction($callback))->invoke();
    }

    private function afterCancelTransaction(\Closure $callback)
    {
        if (isset($this->transactionHandler)) {
            $this->transactionHandler->cancel();
        }

        return (new \ReflectionFunction($callback))->invoke();
    }

    private function updateOrCreateIfMatchCondition($relation, $value)
    {
        if (2 === \count($value)) {
            $relation->updateOrCreate($value[0], $value[1]);
        }
        if (1 === \count($value)) {
            $relation->updateOrCreate($value[0], $value[0]);
        }
    }
}
