<?php

namespace Drewlabs\Packages\Database\Traits;

use Drewlabs\Contracts\Data\Filters\FiltersInterface;
use Drewlabs\Contracts\Data\Model\Parseable;
use Drewlabs\Contracts\Data\Model\Relatable;
use Drewlabs\Core\Data\Exceptions\RepositoryException;
use Drewlabs\Contracts\Data\Model\Model;
use Illuminate\Support\Collection;

trait ModelRepository
{

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
    public function insert(array $values, bool $_ = false, $upsert = false, $upsertConditions = [])
    {
        $that = $this;
        $values = $that->parseInputValues($values);
        $result =  $upsert ?  call_user_func_array(
            [\drewlabs_core_create_attribute_getter('model_instance', null)($that), 'updateOrCreate'],
            [
                !empty($upsertConditions) ? $upsertConditions : $values,
                $values
            ]
        ) :
            \drewlabs_core_create_attribute_getter('model_instance', null)($that)->add($values);
        // $that->resetScope();
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function insertMany(array $values, $_ = true)
    {
        if (\array_filter($values, 'is_array') === $values) {
            $list = array();
            $that = $this;
            // Loop through individual elements and parse the model state
            foreach ($values as $value) {
                // Set timestamps values in case of bulk assignement
                $value = $that->parseInputValues($value);
                $value = array_merge($value, array('updated_at' => date('Y-m-d H:i:s'), 'created_at' => date('Y-m-d H:i:s')));
                $list[] = $value;
            }
            $result = \drewlabs_core_create_attribute_getter('model_instance', null)($that)->{"insert"}($list);
            // $that->resetScope();
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
        // $that->resetScope();
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
        // $that->resetScope();
        return $list;
    }

    /**
     * @inheritDoc
     */
    public function find(array $conditions = array(), array $columns = array('*'))
    {
        $that = $this;
        $model = $that->makeModel();
        $query_model_relation = \drewlabs_core_create_attribute_getter('query_model_relation', false)($that);
        $that =  $query_model_relation ? $that->loadWith(method_exists(
            $model,
            'getModelRelationLoadersNames'
        ) ? call_user_func(array($model, 'getModelRelationLoadersNames')) : []) : clone $that;
        $result = \drewlabs_core_create_attribute_getter(
            'model_instance',
            null
        )($that->applyWhereQuery($conditions))->get($columns);
        // $that->resetScope();
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function findById($id, array $columns = array('*'))
    {
        $that = $this;
        $model = $that->makeModel();
        $prop = \drewlabs_core_create_attribute_getter('ignore_relations_on_single_model', false)($that);
        if (!$prop) {
            $that = $that->queryRelation(true);
        }
        $result = $that->find(array(array($model->getPrimaryKey(), $id)), $columns)->first();
        // $that->resetScope();
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function updateById($id, array $data, bool $parse_inputs = true)
    {
        $count = 0;
        $that = $this;
        $model = $that->makeModel();
        $result = $that->queryRelation(false)->find(array(array($model->getPrimaryKey(), $id)))->first();
        if ($result) {
            if ($parse_inputs) {
                $data = $that->parseInputValues($data);
            }
            $count = intval($result->update($data));
        }
        // $that->resetScope();
        return $count;
    }

    /**
     * @inheritDoc
     */
    public function update(array $values, array $conditions = array(), bool $parse_inputs = true, bool $mass_update =  false)
    {
        $self = $this;
        $count = 0;
        if ($parse_inputs) {
            $values = $self->parseInputValues($values);
        }
        $that = $self->applyWhereQuery($conditions);
        if ($mass_update) {
            // If should mass update the model, mass update it
            $count =  \call_user_func(array(
                \drewlabs_core_create_attribute_getter(
                    'model_instance',
                    null
                )($that), 'update'
            ), $values);
        } else {
            //  Get the list of models that matches the query
            $list = \drewlabs_core_create_attribute_getter(
                'model_instance',
                null
            )($that)->{"get"}();
            // Collect the list if it is an array
            $list = is_array($list) ? new Collection($list) : $list;
            // Loop through all the item in the list and update their field
            $list->each(function ($value) use (&$count, $values) {
                // Then save the model to the database
                \call_user_func(array($value, 'update'), $values);
                $count++;
            });
        }
        // $that->resetScope();
        return $count;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($id)
    {
        $that = $this;
        $model = $that->makeModel();
        $result = $that->queryRelation(false)->find(array(array($model->getPrimaryKey(), $id)))->first();
        $count = 0;
        if ($result) {
            // Then save the model to the database
            \call_user_func(array($result, 'delete'));
            $count = 1;
        }
        // $that->resetScope();
        return $count;
    }

    /**
     * @inheritDoc
     */
    public function delete(array $conditions = array(), bool $mass_delete =  false)
    {
        $deleted = 0;
        // Perform a mass delete on each element of the list of model
        $that = $this->applyWhereQuery($conditions);
        // Perform a mass delete operation
        if ($mass_delete) {
            $deleted = \call_user_func(
                array(
                    \drewlabs_core_create_attribute_getter('model_instance', null)($that), 'delete'
                )
            );
        } else {
            $list = \call_user_func(array(
                \drewlabs_core_create_attribute_getter(
                    'model_instance',
                    null
                )($that), 'get'
            ));
            $list = is_array($list) ? new Collection($list) : $list;
            $list->each(function ($value) use (&$deleted) {
                $deleted += $value->delete();
            });
        }
        // $that->resetScope();
        return $deleted;
    }

    private function applyWhereQuery($conditions)
    {
        $self = $this;
        if (empty($conditions)) {
            $that = $self->applyFilter();
        } else if (drewlabs_core_array_is_array_list($conditions)) {
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
}
