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

namespace Drewlabs\Laravel\Query\Traits;

use Illuminate\Contracts\Routing\UrlRoutable;

trait URLRoutableAware
{
    public function getRouteKey()
    {
        if ($instance = $this->getRoutable()) {
            return $instance->getRouteKey();
        }

        return null;
    }

    public function getRouteKeyName()
    {
        if ($instance = $this->getRoutable()) {
            return $instance->getRouteKeyName();
        }

        return null;
    }

    /**
     * {@inheritDoc}
     * 
     * @param mixed $value
     *
     * @return static|null
     */
    public function resolveRouteBinding($value, $field = null)
    {
        if ($instance = $this->getRoutable()) {
            $value = $instance->resolveRouteBinding($value, $field);

            return $value ? new static($value) : $value;
        }

        return null;
    }

    /**
     * retrieve child model for a bound value.
     * 
     * @param mixed $childType 
     * @param mixed $value 
     * @param mixed $field 
     * 
     * @return URLRoutableAware|null 
     */
    public function resolveChildRouteBinding($childType, $value, $field)
    {
        if ($routable = $this->getRoutable()) {
            $value = $routable->resolveChildRouteBinding($childType, $value, $field);
            return $value ? new static($value) : $value;
        }

        return null;
    }

    protected function getRoutable()
    {

        if (!method_exists($this, 'getModel')) {
            return null;
        }

        $model = $this->getModel();
        if ($model instanceof UrlRoutable) {
            return $model;
        }

        return null;
    }
}
