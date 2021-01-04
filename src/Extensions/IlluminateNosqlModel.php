<?php

namespace Drewlabs\Packages\Database\Extensions;

use Drewlabs\Core\Database\NoSql\NosqlModel as NoSqlBaseModel;
use Drewlabs\Contracts\Data\ParseableModelInterface;

final class IlluminateNosqlModel extends NoSqlBaseModel implements ParseableModelInterface
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
     * Dictionnary mapping of the fillable entries of the model and the request inputs
     *
     * @return array
     */
    public function getModelStateMap()
    {
        return $this->model_states ?? [];
    }

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
