<?php

namespace Drewlabs\Packages\Database\Traits;

trait HavingBooleanAttributes
{
    public function getAttribute($name)
    {
        if (
            in_array($name, [
                'hidden',
                'is_active',
                'is_verified',
                'freezed',
                'disbaled',
            ]) && property_exists($this, 'attributes')
        ) {
            return filter_var($this->attributes[$name] ?? null, FILTER_VALIDATE_BOOLEAN);
        }
        return parent::getAttribute($name);
    }
}
