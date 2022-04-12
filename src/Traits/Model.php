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

namespace Drewlabs\Packages\Database\Traits;

trait Model
{
    use AppendedAttributes;
    use GuardedModel;
    use LinkAttributeAware;

    /**
     * {@inheritDoc}
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
     * {@inheritDoc}
     */
    public function getAll(bool $relations = false, array $columns = ['*'])
    {
        if ($relations) {
            return $this->with($this->getDeclaredRelations())->get($columns);
        }
        return $this->get($columns);
    }

    /**
     * @deprecated v2.1.x
     */
    public function getFillables()
    {
        return $this->fillable ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function getRelations()
    {
        return $this->relations ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey ?? 'id';
    }

    public function setKey($value)
    {
        $this->{$this->getPrimaryKey()} = $value;

        return $this;
    }

    /**
     * @deprecated v2.x.0 use {@see getDeclaredRelations()} instead
     */
    public function getModelRelationLoadersNames()
    {
        return $this->getDeclaredRelations();
    }

    public function getDeclaredRelations()
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
                $this->getFillable() ?? [],
                $this->getGuarded() ?? [],
                $this->timestamps ? $timestamps : [],
                $primaryKey ? [$primaryKey] : []
            )
        );
    }

    /**
     * Checks if the current model has some relations.
     *
     * @return bool
     */
    protected function hasRelations()
    {
        return \is_array($this->getRelations()) ?: false;
    }
}
