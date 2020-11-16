<?php

namespace Drewlabs\Packages\Database;

use Drewlabs\Contracts\Data\DataRepository\Repositories\IModelRepository;
use Drewlabs\Packages\Database\Contracts\TransactionUtils;

class DynamicCRUDQueryHandler
{

    /**
     *
     * @var \Drewlabs\Packages\Database\Contracts\TransactionUtils
     */
    public $transactionHandler;

    /**
     *
     * @var IModelRepository
     */
    public $repository;

    /**
     * @return static
     */
    public function bindTransactionHandler(TransactionUtils $hanlder)
    {
        return drewlabs_core_create_attribute_setter('transactionHandler', $hanlder)($this);
    }

    /**
     * @return static
     */
    public function bindRepository(IModelRepository $repository)
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
        if (is_null($this->repository) || !($this->repository instanceof IModelRepository)) {
            throw new \RuntimeException('Calling ' . __METHOD__ . ' requires binding the repository first. Call bindRepository($repository) method before calling this method');
        }
        try {
            if (isset($this->transactionHandler)) {
                $this->transactionHandler->startTransaction();
            }
            $model = $this->repository->insert($values, $parse_inputs, $upsert, $conditions);
            foreach ($relations as $i) {
                # code...
                if (method_exists($model, $i) && array_key_exists($i, $values)  && isset($values[$i])) {
                    $isArrayList = \array_filter($values[$i], 'is_array') === $values[$i];
                    if ($isArrayList) {
                        // If specified to insert item in mass, insert all entries in one query
                        if ($mass_insert) {
                            $model->{$i}()->createMany(array_map(function ($value) {
                                return array_merge(
                                    $value,
                                    array(
                                        'created_at' => date('Y-m-d H:i:s'),
                                        'updated_at' => date('Y-m-d H:i:s')
                                    )
                                );
                            }, $values[$i]));
                        } else {
                            // Else insert each entry individually to provide user of the method
                            // the ability to listen for each insertion event
                            foreach ($values[$i] as $k) {
                                # code...
                                $model->{$i}()->create($k);
                            }
                        }
                    } else {
                        $model->{$i}()->create($values[$i]);
                    }
                }
            }
            if (isset($this->transactionHandler)) {
                $this->transactionHandler->completeTransaction();
            }
            return $model;
        } catch (\Exception $e) {

            if (isset($this->transactionHandler)) {
                $this->transactionHandler->cancel();
            }
            throw new \RuntimeException($e);
        }
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
        if (is_null($this->repository) || !($this->repository instanceof IModelRepository)) {
            throw new \RuntimeException('Calling ' . __METHOD__ . ' requires binding the repository first. Call bindRepository($repository) method before calling this method');
        }
        try {
            if (isset($this->transactionHandler)) {
                $this->transactionHandler->startTransaction();
            }
            $updated = 0;
            $updated = $this->repository->updateById($id, $values, $parse_inputs);
            $model = $this->repository->findById($id, array($this->repository->{'modelPrimaryKey'}()));
            if (!is_null($model)) {
                foreach ($relations as $i) {
                    # code...
                    if (method_exists($model, $i) && array_key_exists($i, $values) && isset($values[$i])) {
                        if ($upsert) {
                            $isArrayList = isset($values[$i][0]) && \array_filter($values[$i][0], 'is_array') === $values[$i][0];
                            if ($isArrayList) {
                                foreach ($values[$i] as $v) {
                                    # code...
                                    $this->updateOrCreateIfMatchCondition($model->{$i}(), $v);
                                }
                            } else {
                                $this->updateOrCreateIfMatchCondition($model->{$i}(), $values[$i]);
                            }
                        } else {
                            $isArrayList = isset($values[$i]) && \array_filter($values[$i], 'is_array') === $values[$i];
                            if ($isArrayList) {
                                $model->{$i}()->delete();
                                // Create many after deleting the all the related
                                $model->{$i}()->createMany(array_map(function ($value) use ($model) {
                                    return array_merge(
                                        $value,
                                        array(
                                            'created_at' => date('Y-m-d H:i:s'),
                                            'updated_at' => date('Y-m-d H:i:s')
                                        )
                                    );
                                }, $values[$i]));
                            } else {
                                $model->{$i}()->delete();
                                $model->{$i}()->create($values[$i]);
                            }
                        }
                    }
                }
            }
            if (isset($this->transactionHandler)) {
                $this->transactionHandler->completeTransaction();
            }
            return $updated;
        } catch (\Exception $e) {
            if (isset($this->transactionHandler)) {
                $this->transactionHandler->cancel();
            }
            throw new \RuntimeException($e);
        }
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
