<?php

namespace Drewlabs\Packages\Database\Traits;

trait WithHiddenModelTrait
{
    /**
     * Returns the attached model hidden property
     *
     * @return array
     */
    public function getHidden()
    {
        return $this->hidden;
    }
    /**
     * Set the attached model hidden property
     *
     * @param array $attributes
     * @return static
     */
    public function setHidden(array $attributes)
    {
        $this->hidden = $attributes;
        return $this;
    }
}
