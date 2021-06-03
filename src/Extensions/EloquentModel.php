<?php

namespace Drewlabs\Packages\Database\Extensions;

use Drewlabs\Contracts\Data\Model\ActiveModel;
use Drewlabs\Contracts\Data\Model\GuardedModel;
use Drewlabs\Contracts\Data\Model\Parseable;
use Drewlabs\Contracts\Data\Model\Relatable;
use Drewlabs\Packages\Database\Traits\HavingBooleanAttributes;
use Drewlabs\Packages\Database\Traits\WithHiddenModelTrait;
use Illuminate\Database\Eloquent\Model as Eloquent;

abstract class EloquentModel extends Eloquent implements ActiveModel, Parseable, Relatable, GuardedModel
{
    use \Drewlabs\Packages\Database\Traits\RoutableModel;
    use \Drewlabs\Packages\Database\Traits\ModelAppendsTrait;
    use \Drewlabs\Packages\Database\Traits\GuardedModelTrait;
    use WithHiddenModelTrait;
    use HavingBooleanAttributes;

    /**
     * Fillable storage columns of an entity
     *
     * @return array
     */
    protected $fillable = [];
    /**
     * Property for controlling if appended contents should be added to the model query json result
     *
     * @var boolean
     */
    protected $withoutAppends = false;

    /**
     * List of methods or dynamic properties that can be loaded as model relations
     *
     * @var array
     */
    protected $relation_methods = [];

    /**
     * Checks if the current model has some relations
     *
     * @return boolean
     */
    protected function hasRelations()
    {
        return is_array($this->getRelations()) ?: false;
    }

    /**
     * @inheritDoc
     */
    public function add(array $items)
    {
        $isArrayList = \array_filter($items, 'is_array') === $items;
        if (!$isArrayList) {
            return $this->create($items);
        }
        return $this->insert($items);
    }

    /**
     * @inheritDoc
     */
    public function getAll(bool $relations = false, array $columns = array('*'))
    {
        if ($relations) {
            return $this->with($this->getModelRelationLoadersNames())->get($columns);
        }
        return $this->get($columns);
    }

    /**
     * @inheritDoc
     */
    public function getEntity()
    {
        return $this->table;
    }

    /**
     * @inheritDoc
     */
    public function getEntityUniqueName()
    {
        return $this->entityIdentifier;
    }

    /**
     * @inheritDoc
     */
    public function getFillables()
    {
        return $this->fillable ?? [];
    }

    /**
     * @inheritDoc
     */
    public function getRelations()
    {
        return $this->relations ?? [];
    }

    /**
     * @inheritDoc
     */
    public function getPrimaryKey()
    {
        return isset($this->primaryKey) ? $this->primaryKey : 'id';
    }

    /**
     * @inheritDoc
     */
    public function setPrimaryKey($value)
    {
        $this->{$this->primaryKey} = $value;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getModelRelationLoadersNames()
    {
        return $this->relation_methods ?? [];
    }
}
