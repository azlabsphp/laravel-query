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

use Drewlabs\Core\Helpers\Arr;

trait Model
{
    public function getRelations()
    {
        return $this->relations ?? [];
    }

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
        if (\is_bool($primaryKey)) {
            print_r(__CLASS__);
            exit();
        }
        // Get list of fillables
        return Arr::unique(array_merge(
            $this->getFillable() ?? [],
            $this->getGuarded() ?? [],
            // Case the timestamps are not on the fillables, we simply add them
            // to support query by created_at & updated_at, as it does
            // no harm to the implementation
            ['created_at', 'updated_at'],
            $primaryKey ? [$primaryKey] : []
        ));
    }

    public function getGuardedAttributes()
    {
        return $this->guarded ?? [];
    }

    protected function hasRelations()
    {
        return \is_array($this->getRelations()) ?: false;
    }
}
