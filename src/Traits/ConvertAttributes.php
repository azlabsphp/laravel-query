<?php

namespace Drewlabs\Packages\Database\Traits;

use Drewlabs\Core\Helpers\Arr;

trait ConvertAttributes
{
    /**
     * Creates attributes array from mixed type
     * 
     * @param array|object $attributes 
     * @return array 
     */
    protected function attributesToArray($attributes)
    {
        if (is_array($attributes)) {
            return $attributes;
        }
        return Arr::create($attributes);
    }
}