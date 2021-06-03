<?php

namespace Drewlabs\Packages\Database\Extensions;

use Drewlabs\Contracts\Data\Model\Parseable;
use Drewlabs\Core\Database\NoSql\NosqlModel as NoSqlBaseModel;

final class IlluminateNosqlModel extends NoSqlBaseModel implements Parseable
{
    use \Drewlabs\Packages\Database\Traits\GuardedModelTrait;
    use \Drewlabs\Packages\Database\Traits\WithHiddenModelTrait;

    /**
     * Fillable storage columns of an entity
     *
     * @return array
     */
    protected $fillable = [];


    /**
     * Returns the fillable properties of the given model
     *
     * @return array
     */
    public function getFillables()
    {
        return $this->fillable ?? [];
    }
}
