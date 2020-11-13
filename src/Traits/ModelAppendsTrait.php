<?php

namespace Drewlabs\Packages\Database\Traits;

trait ModelAppendsTrait
{


    protected function getArrayableAppends()
    {
        $route = $this->getIndexRoute();
        if ($this->withoutAppends) {
            return !is_null($route) && is_string($route) ? array('_link') : array();
        }
        return array_merge(parent::getArrayableAppends(), isset($route) && is_string($route) ? array('_link') : array());
    }

    /**
     * Set the value of the withoutAppends property
     *
     * @param bool $value
     * @return static
     */
    public function setWithoutAppends($value)
    {
        $this->withoutAppends = $value;
        return $this;
    }
}
