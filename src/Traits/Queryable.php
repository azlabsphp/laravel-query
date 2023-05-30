<?php

declare(strict_types=1);

/*
 * This file is part of the drewlabs namespace.
 *
 * (c) Sidoine Azandrew <azandrewdevelopper@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drewlabs\LaravelQuery\Traits;

use Drewlabs\Core\Helpers\Arr;

trait Queryable
{
    // #region primary keys
    public function getPrimaryKey()
    {
        return $this->primaryKey ?? 'id';
    }

    public function setKey($value)
    {
        $this->{$this->getPrimaryKey()} = $value;

        return $this;
    }
    // #endregion primary keys

    // #region Queryable columns
    public function getDeclaredColumns()
    {
        // Get table primary key
        $primaryKey = $this->getPrimaryKey();

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
    // #endregion Queryable columns

    // #region Hides attributes
    public function getHidden()
    {
        return $this->hidden;
    }

    public function setHidden(array $attributes)
    {
        $this->hidden = $attributes;

        return $this;
    }
    // #endregion Hides attributes

    // #region relations
    public function getRelations()
    {
        return $this->relations ?? [];
    }

    public function getDeclaredRelations()
    {
        return $this->relation_methods ?? [];
    }

    protected function hasRelations()
    {
        return \is_array($this->getRelations()) ?: false;
    }
    // #endregion relations
}