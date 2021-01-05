<?php

namespace Drewlabs\Packages\Database\Extensions;

use Drewlabs\Contracts\Data\GuardedModelInterface;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Drewlabs\Contracts\Data\ModelInterface;
use Drewlabs\Contracts\Data\ParseableModelInterface;
use Drewlabs\Contracts\Data\RelatedModelInterface;


abstract class EloquentModel extends Eloquent implements ModelInterface, ParseableModelInterface, RelatedModelInterface, GuardedModelInterface
{
    use \Drewlabs\Packages\Database\Traits\RoutableModel;
    use \Drewlabs\Packages\Database\Traits\ModelAppendsTrait;
    use \Drewlabs\Packages\Database\Traits\GuardedModelTrait;

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
