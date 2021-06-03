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
        return $upsert ?  call_user_func_array(
            [\drewlabs_core_create_attribute_getter('model_instance', null)($that), 'updateOrCreate'],
            [
                !empty($upsertConditions) ? $upsertConditions : $values,
                $values
            ]
        ) :
            \drewlabs_core_create_attribute_getter('model_instance', null)($that)->add($values);
    }

    /**
     * @inheritDoc
     */
    public function insertMany(array $values, $_ = true)
    {
        if (\array_filter($values, 'is_array') === $values) {
            $list = array();
            $self = $this;
            // Loop through individual elements and parse the model state
            foreach ($values as $value) {
                // Set timestamps values in case of bulk assignement
                $value = $self->parseInputValues($value);
                $value = array_merge($value, array('updated_at' => date('Y-m-d H:i:s'), 'created_at' => date('Y-m-d H:i:s')));
                $list[] = $value;
            }
            return \drewlabs_core_create_attribute_getter('model_instance', null)($self)->{"insert"}($list);
        }
        throw new RepositoryException(__METHOD__ . ' requires an list of list items for insertion');
    }

    /**
     * @inheritDoc
     */
    public function all($columns = array('*'))
    {
        $self = $this;
        return $self->makeModel()->getAll(
            \drewlabs_core_create_attribute_getter('query_model_relation', false)($self),
            $columns
        );
    }

    /**
     * @inheritDoc
     */
    public function paginate($perPage = 20, $columns = array('*'))
    {
        $self = $this;
        $model = $self->makeModel();
        $query_model_relation = \drewlabs_core_create_attribute_getter('query_model_relation', false)($self);
        if ($query_model_relation) {
            $self = $self->loadWith(
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
                )($self->applyFilter()), 'paginate'
            ],
            [
                $perPage,
                $columns
            ]
        );
        return $list;
    }

    /**
     * @inheritDoc
     */
    public function find(array $conditions = array(), array $columns = array('*'))
    {
        $self = $this;
        $model = $self->makeModel();
        $query_model_relation = \drewlabs_core_create_attribute_getter('query_model_relation', false)($self);
        $that =  $query_model_relation ? $self->loadWith(method_exists(
            $model,
            'getModelRelationLoadersNames'
        ) ? call_user_func(array($model, 'getModelRelationLoadersNames')) : []) : clone $self;
        if (empty($conditions)) {
            return \drewlabs_core_create_attribute_getter(
                'model_instance',
                null
            )($that->applyFilter())->{"get"}($columns);
        } else {
            return \drewlabs_core_create_attribute_getter(
                'model_instance',
                null
            )($that)->{'where'}($conditions)->get($columns);
        }
    }

    /**
     * @inheritDoc
     */
    public function findById($id, array $columns = array('*'))
    {
        $self = $this;
        $model = $self->makeModel();
        return $self->queryRelation(true)->find(array(array($model->getPrimaryKey(), $id)), $columns)->first();
    }

    /**
     * @inheritDoc
     */
    public function updateById($id, array $data, bool $parse_inputs = true)
    {
        $self = $this;
        $model = $self->makeModel();
        $result = $self->queryRelation(false)->find(array(array($model->getPrimaryKey(), $id)))->first();
        if ($result) {
            if ($parse_inputs) {
                $data = $self->parseInputValues($data);
            }
            return intval($result->update($data));
        }
        return 0;
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
        $that = empty($conditions) ? $self->applyFilter() : \drewlabs_core_create_attribute_setter('model_instance', \drewlabs_core_create_attribute_getter('model_instance', null)($self)->{'where'}($conditions))($self);
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
        return $count;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($id)
    {
        $self = $this;
        $model = $self->makeModel();
        $result = $self->queryRelation(false)->find(array(array($model->getPrimaryKey(), $id)))->first();
        if ($result) {
            // Then save the model to the database
            \call_user_func(array($result, 'delete'));
            return 1;
        }
        return 0;
    }

    /**
     * @inheritDoc
     */
    public function delete(array $conditions = array(), bool $mass_delete =  false)
    {
        $self = $this;
        // Perform a mass delete on each element of the list of model
        $deleted = 0;
        $that = empty($conditions) ? $self->applyFilter() : \drewlabs_core_create_attribute_setter(
            'model_instance',
            \drewlabs_core_create_attribute_getter('model_instance', null)($self)->{'where'}($conditions)
        )($self);
        // Perform a mass delete operation
        if ($mass_delete) {
            return \call_user_func(
                array(
                    \drewlabs_core_create_attribute_getter('model_instance', null)($self), 'delete'
                )
            );
        }
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
        return $deleted;
    }
}
