<?php

namespace Drewlabs\Packages\Database\Traits;

trait Model
{

    use RoutableModel;
    use ModelAppendsTrait;
    use GuardedModelTrait;
    use WithHiddenModelTrait;
    use HavingBooleanAttributes;

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
        $isArrayList = drewlabs_core_array_is_no_assoc_array_list($items);
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
     * @deprecated v3.1
     * 
     * @inheritDoc
     */
    public function setPrimaryKey($value)
    {
        return $this->setKey($value);
    }

    public function setKey($value)
    {
        $this->{$this->getPrimaryKey()} = $value;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getModelRelationLoadersNames()
    {
        return $this->relation_methods ?? [];
    }

    public function getDeclaredColumns()
    {
        // Get table primary key
        $primaryKey = $this->getPrimaryKey();
        // Get timestamps columns
        $timestamps = array_merge(
            method_exists($this, 'getCreatedAtColumn') ?
                [$this->getCreatedAtColumn()] :
                ['created_at'],
            method_exists($this, 'getUpdatedAtColumn') ?
                [$this->getUpdatedAtColumn()] :
                ['updated_at']
        );
        // Get list of fillables
        return drewlabs_core_array_unique(
            array_merge(
                $this->getFillables() ?? [],
                $this->getGuardedAttributes() ?? [],
                $this->timestamps ? $timestamps : [],
                $primaryKey ? [$primaryKey] : []
            )
        );
    }
}
