<?php

namespace Drewlabs\Packages\Database\Traits;

trait HavingBooleanAttributes
{
    public function getStatusAttribute()
    {
        return isset($this->attributes['status']) ? filter_var($this->attributes['status'], FILTER_VALIDATE_BOOLEAN) : false;
    }

    public function getHiddenAttribute()
    {
        return isset($this->attributes['hidden']) ? filter_var($this->attributes['hidden'], FILTER_VALIDATE_BOOLEAN) : false;
    }

    public function getIsActiveAttribute()
    {
        return isset($this->attributes['is_active']) ? filter_var($this->attributes['is_active'], FILTER_VALIDATE_BOOLEAN) : false;
    }

    public function getIsVerifiedAttribute()
    {
        return isset($this->attributes['is_verified']) ? filter_var($this->attributes['is_verified'], FILTER_VALIDATE_BOOLEAN) : false;
    }
}
