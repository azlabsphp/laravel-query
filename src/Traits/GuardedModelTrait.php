<?php

namespace Drewlabs\Packages\Database\Traits;

trait GuardedModelTrait
{

    /**
     * @inheritDoc
     */
    public function getGuardedAttributes()
    {
        return $this->guarded;
    }
}
