<?php

namespace Drewlabs\Packages\Database\Traits;

use Drewlabs\Contracts\Data\Filters\FiltersInterface;
use Drewlabs\Contracts\Data\Model\Parseable;
use Drewlabs\Contracts\Data\Model\Relatable;
use Drewlabs\Core\Data\Exceptions\RepositoryException;
use Drewlabs\Contracts\Data\Model\Model;
use Drewlabs\Core\Support\Traits\Overloadable;
use Illuminate\Support\Collection;

trait ModelRepository
{
    use Overloadable;

    /**
     * {@inheritDoc}
     */
    public function queryRelation(bool $value = true)
    {
        return drewlabs_core_create_attribute_setter('query_model_relation', $value)($this);
    }

    /**
     * @inheritDoc
     */
    public function loadWith($relations)
    {
        $relations = !drewlabs_core_array_is_arrayable($relations) || is_null($relations) ? [] : $relations;
        return drewlabs_core_create_attribute_setter(
            'model_instance',
            call_user_func([\drewlabs_core_create_attribute_getter('model_instance', null)($this), 'with'], $relations)
        )($this);
    }

    protected function parseInputValues(array $values)
    {
        $self = $this;
        $model = $self->makeModel();
        if (!(method_exists($model, 'getFillables')) && !($model instanceof Parseable)) {
            return $values;
        }
        $values = $self->modelAttributesParser()->setModel($model)->setModelInputState($values)->getModelInputState();
        return $values;
    }

    /**
     * @inheritDoc
     */
    public function resetScope()
    {
        return $this;
    }


    /**
     * @inheritDoc
     */
    public function skipFilters($value = true)
    {
        return drewlabs_core_create_attribute_setter('skip_filters', $value)($this);
    }

    /**
     * @inheritDoc
     */
    public function getFilters()
    {
        return \drewlabs_core_create_attribute_getter('filters', [])($this);
    }

    /**
     * @param ModelFiltersInterface $filter
     * @return static
     */
    public function getByFilter($filter)
    {
        $self = $this;
        return drewlabs_core_create_attribute_setter(
            'model_instance',
            $filter->apply(
                \drewlabs_core_create_attribute_getter('model_instance', null)($self)
            )
        )($self);
    }

    /**
     * @param FiltersInterface $filter
     * @return static
     */
    public function pushFilter(FiltersInterface $filter)
    {
        $self = $this;
        $filters = \drewlabs_core_create_attribute_getter('filters', [])($self);
        $filters[] = $filter;
        return drewlabs_core_create_attribute_setter(
            'filters',
            $filters
        )($self);
    }

    /**
     * @return static
     */
    public function applyFilter()
    {
        $that = $this;
        $skip_filters = \drewlabs_core_create_attribute_getter('skip_filters', [])($that);
        if ($skip_filters === true) {
            return $that;
        }
        foreach ($that->getFilters() as $filter) {
            if (($filter instanceof FiltersInterface)) {
                $that = drewlabs_core_create_attribute_setter('model_instance', $filter->apply(
                    \drewlabs_core_create_attribute_getter('model_instance', null)($that)
                ))($that);
            }
        }
        return $that;
    }

    /**
     * Model instance variable
     *
     * @var Parseable|Model|Relatable
     */
    protected $model;

    /**
     * @inheritDoc
     */
    public function insert(...$args)
    {
        return $this->overload($args, [
            'insertV1',
            'insertV2',
            'insertv3'
        ]);
    }

    public function insertV1(array $values, bool $parse_inputs = false, $upsert = false, array $upsertConditions = [])
    {
        if ($upsert) {
            return $this->insertv3($values ?? [], $upsertConditions ?? []);
        }
        return $this->insertV2($values);
    }

    public function insertV2(array $values)
    {
        return \drewlabs_core_create_attribute_getter('model_instance', null)($this)->add($values);
    }

    public function insertv3(array $values, array $conditions)
    {
        return call_user_func_array(
            [\drewlabs_core_create_attribute_getter('model_instance', null)($this), 'updateOrCreate'],
            [
                !empty($conditions) ? $conditions : [],
                $values
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function insertMany(...$args)
    {
        return $this->overload($args, [
            'insertManyV0',
            'insertManyV1',
            'insertManyV2'
        ]);
    }

    public function insertManyV0(array $values, bool $parse_inputs = false, bool $_ = false, array $__ = [])
    {
        return $this->insertManyV1($values, $parse_inputs);
    }

    public function insertManyV1(array $values, bool $parse_inputs = true)
    {
        return $this->insertManyV2($values);
    }

    public function insertManyV2($values)
    {
        if (\array_filter($values, 'is_array') === $values) {
            $list = array();
            $that = $this;
            // Loop through individual elements and parse the model state
            foreach ($values as $value) {
                // Set timestamps values in case of bulk assignement
                $value = $that->parseInputValues($value);
                $value = array_merge(
                    $value,
                    array('updated_at' => date('Y-m-d H:i:s'), 'created_at' => date('Y-m-d H:i:s'))
                );
                $list[] = $value;
            }
            $result = \drewlabs_core_create_attribute_getter('model_instance', null)($that)->{"insert"}($list);
            return $result;
        }
        throw new RepositoryException(__METHOD__ . ' requires an list of list items for insertion');
    }

    /**
     * @inheritDoc
     */
    public function all($columns = array('*'))
    {
        $that = $this;
        $result = $that->makeModel()->getAll(
            \drewlabs_core_create_attribute_getter('query_model_relation', false)($that),
            $columns
        );
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function paginate($perPage = 20, $columns = array('*'))
    {
        $that = $this;
        $model = $that->makeModel();
        $query_model_relation = \drewlabs_core_create_attribute_getter('query_model_relation', false)($that);
        if ($query_model_relation) {
            $that = $that->loadWith(
                method_exists(
                    $model,
                    'getModelRelationLoadersNames'
                ) ? call_user_func(array($model, 'getModelRelationLoadersNames')) : []
            );
        }
        $list =  call_user_func_array(
            [
                \drewlabs_core_create_attribute_getter(
                    'model_instance',
                    null
                )($that->applyFilter()), 'paginate'
            ],
            [
                $perPage,
                $columns
            ]
        );
        return $list;
    }

    /**
     * Query for a row/rows from the database using user provided argument
     *
     * @param array ..$args
     */
    public function find(...$args)
    {
        return $this->overload($args, [
            'findByIntId',
            'findByStringID',
            'findByArrayConditions',
            'findFromFilters'
        ]);
    }

    public function findByStringID(string $id, array $columns = ['*'])
    {
        $model = $this->makeModel();
        $that = $this->applyQueryRelationOnSingleRowSelection();
        return $that->findByArrayConditions(array(array($model->getPrimaryKey(), $id)), $columns)->first();
    }

    public function findByIntId(int $id, array $columns = ['*'])
    {
        return $this->findByStringID((string)$id, $columns);
    }

    public function findByArrayConditions(array $conditions, array $columns = ['*'])
    {
        $that = $this;
        $model = $that->makeModel();
        $query_model_relation = \drewlabs_core_create_attribute_getter('query_model_relation', false)($that);
        return \drewlabs_core_create_attribute_getter(
            'model_instance',
            null
        )(($query_model_relation ? $that->loadWith(method_exists(
            $model,
            'getModelRelationLoadersNames'
        ) ? call_user_func(array($model, 'getModelRelationLoadersNames')) : []) : clone $that)->applyWhereQuery($conditions))->get($columns);
    }

    public function findFromFilters(array $columns = ['*'])
    {
        // Create the model instance
        $model = $this->makeModel();
        $that = $this->applyFilter();
        $query_model_relation = \drewlabs_core_create_attribute_getter('query_model_relation', false)($that);
        return \drewlabs_core_create_attribute_getter(
            'model_instance',
            null
        )($query_model_relation ? $that->loadWith(method_exists(
            $model,
            'getModelRelationLoadersNames'
        ) ? call_user_func(array($model, 'getModelRelationLoadersNames')) : []) : clone $that)->get($columns);
    }

    /**
     * @deprecated v3.0.1 Use the {@see find()} overloaded method that takes in an id
     */
    public function findById($id, array $columns = array('*'))
    {
        return $this->findByIntId($id, $columns);
    }

    /**
     * @inheritDoc
     */
    public function updateById($id, array $data, bool $parse_inputs = true)
    {
        return $this->updateV1($id, $data);
    }

    /**
     * @inheritDoc
     */
    public function update(...$args)
    {
        return $this->overload($args, [
            'updateV1',
            'updateV1_1',
            'updateV2',
            'updateV2_1',
            'updateV3',
            'updateV4',
            'updateV5',
            'updateV6',
            'updateV7'
        ]);
    }

    public function updateV1_1(string $id, $attributes = [], bool $_ = false, bool $__ = true)
    {
        return $this->updateV1($id, $attributes);
    }

    public function updateV1(string $id, $attributes = [])
    {
        $that = $this;
        $model = $that->makeModel();
        $attributes = $that->parseInputValues($attributes);
        $result = $that->queryRelation(false)->findByArrayConditions(
            array(
                array($model->getPrimaryKey(), $id)
            )
        )->first();
        return is_null($result) ? 0 : intval($result->update($attributes));
    }

    public function updateV2_1(int $id, $attributes = [], bool $_ = false, bool $__ = true)
    {
        return $this->updateV2($id, $attributes);
    }

    public function updateV2(int $id, array $attributes = [])
    {
        return $this->updateV1((string)$id, $attributes);
    }

    public function updateV3(array $values)
    {
        $values = $this->parseInputValues($values);
        return \call_user_func(array(
            \drewlabs_core_create_attribute_getter(
                'model_instance',
                null
            )($this->applyFilter()), 'update'
        ), $values);
    }

    public function updateV4(array $values, bool $hot_operation = false)
    {
        if (!$hot_operation) {
            $values = $this->parseInputValues($values);
            $list = $this->findFromFilters();
            $list = is_array($list) ? new Collection($list) : $list;
            // Loop through all the item in the list and update their field
            return $list->reduce(function ($carr, $model) use ($values) {
                // Then save the model to the database
                $model = array_reduce(array_keys($values), function ($carr, $curr) use ($values) {
                    call_user_func_array([$carr, 'setAttribute'], [$curr, $values[$curr]]);
                    return $carr;
                }, $model);
                \call_user_func(array($model, 'save'), []);
                $carr += 1;
                return $carr;
            }, 0);
        }
        return $this->updateV3($values);
    }

    public function updateV5(array $values, array $conditions = [])
    {
        $values = $this->parseInputValues($values);
        return \call_user_func(array(
            \drewlabs_core_create_attribute_getter(
                'model_instance',
                null
            )($this->applyWhereQuery($conditions)), 'update'
        ), $values);
    }

    /**
     * @deprecated v3.1
     * Provides support for previous signature of the update method on query filters
     */
    public function updateV6(array $values, array $conditions, bool $_ = true, bool $hot_operation = false)
    {
        return $this->updateV7($values, $conditions, $hot_operation);
    }

    public function updateV7(array $values, array $conditions, bool $hot_operation = false)
    {
        if (!$hot_operation) {
            $values = $this->parseInputValues($values);
            $list = $this->findByArrayConditions($conditions);
            $list = is_array($list) ? new Collection($list) : $list;
            // Loop through all the item in the list and update their field
            return $list->reduce(function ($carr, $model) use ($values) {
                // Then save the model to the database
                $model = array_reduce(array_keys($values), function ($carr, $curr) use ($values) {
                    call_user_func_array([$carr, 'setAttribute'], [$curr, $values[$curr]]);
                    return $carr;
                }, $model);
                \call_user_func(array($model, 'save'), []);
                // \call_user_func(array($value, 'update'), $values);
                $carr += 1;
                return $carr;
            }, 0);
        }
        return $this->updateV5($values, $conditions);
    }

    /**
     * @inheritDoc
     */
    public function deleteById($id)
    {
        return $this->deleteByIntID($id);
    }

    /**
     * @inheritDoc
     */
    public function delete(...$args)
    {
        return $this->overload($args, [
            'deleteByStringID',
            'deleteByIntID',
            'deleteFromFilters',
            'deleteFromFilters_',
            'deleteFromConditions',
            'deleteFromConditions_'
        ]);
    }



    public function deleteByStringID(string $id)
    {
        $that = $this;
        $model = $that->makeModel();
        $result = $that->queryRelation(false)->findByArrayConditions(
            array(
                array(
                    $model->getPrimaryKey(),
                    $id
                )
            )
        )->first();
        // Then save the model to the database
        return !is_null($result) ? intval(\call_user_func(array($result, 'delete'))) : 0;
    }

    public function deleteByIntID(int $id)
    {
        return $this->deleteByStringID((string)$id);
    }

    public function deleteFromFilters()
    {
        \call_user_func(
            array(
                \drewlabs_core_create_attribute_getter('model_instance', null)($this->applyFilter()), 'delete'
            )
        );
        return intval(true);
    }

    public function deleteFromFilters_(bool $hot_operation = false)
    {
        if (!$hot_operation) {
            $list = $this->findFromFilters();
            $list = is_array($list) ? new Collection($list) : $list;
            // Loop through all the item in the list and delete their field
            return $list->reduce(function ($carr, $model) {
                \call_user_func(array($model, 'delete'), []);
                $carr += 1;
                return $carr;
            }, 0);
        }
        return $this->deleteFromFilters();
    }

    public function deleteFromConditions(array $conditions = [])
    {
        return \call_user_func(
            array(
                \drewlabs_core_create_attribute_getter(
                    'model_instance',
                    null
                )($this->applyWhereQuery($conditions)), 'delete'
            ),
            []
        );
    }

    public function deleteFromConditions_(array $conditions, bool $hot_operation = false)
    {
        if (!$hot_operation) {
            $list = $this->findByArrayConditions($conditions);
            $list = is_array($list) ? new Collection($list) : $list;
            // Loop through all the item in the list and delete their field
            return $list->reduce(function ($carr, $model) {
                \call_user_func(array($model, 'delete'), []);
                $carr += 1;
                return $carr;
            }, 0);
        }
        return $this->deleteFromConditions($conditions);
    }

    private function applyWhereQuery($conditions)
    {
        $self = $this;
        if (empty($conditions)) {
            $that = $self->applyFilter();
        } else if (drewlabs_core_array_is_no_assoc_array_list($conditions)) {
            $that = \drewlabs_core_create_attribute_setter(
                'model_instance',
                \drewlabs_core_create_attribute_getter(
                    'model_instance',
                    null
                )($self)->{'where'}($conditions)
            )($self);
        } else {
            $that = \drewlabs_core_create_attribute_setter(
                'model_instance',
                \drewlabs_core_create_attribute_getter(
                    'model_instance',
                    null
                )($self)->{'where'}(...$conditions)
            )($self);
        }
        return $that;
    }

    private function applyQueryRelationOnSingleRowSelection()
    {
        $prop = \drewlabs_core_create_attribute_getter('ignore_relations_on_single_model', false)($this);
        return !$prop ? $this->queryRelation(true) : $this;
    }
}
