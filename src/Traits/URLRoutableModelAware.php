<?php

declare(strict_types=1);

/*
 * This file is part of the Drewlabs package.
 *
 * (c) Sidoine Azandrew <azandrewdevelopper@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drewlabs\Packages\Database\Traits;

trait URLRoutableModelAware
{
    use ContainerAware;

    public function getRouteKey()
    {
        if ($instance = $this->getInstance()) {
            return $instance->getRouteKey();
        }

        return null;
    }

    public function getRouteKeyName()
    {
        if ($instance = $this->getInstance()) {
            return $instance->getRouteKeyName();
        }

        return null;
    }

    /**
     * {@inheritDoc}
     *
     * @return self|null
     */
    public function resolveRouteBinding($value, $field = null)
    {
        if ($instance = $this->getInstance()) {
            $value = $instance->resolveRouteBinding($value, $field);

            return $value ? new self($value) : $value;
        }

        return null;
    }

    /**
     * {@inheritDoc}
     *
     * @return self|null
     */
    public function resolveChildRouteBinding($childType, $value, $field)
    {
        if ($instance = $this->getInstance()) {
            $value = $instance->resolveChildRouteBinding($childType, $value, $field);

            return $value ? new self($value) : $value;
        }

        return null;
    }

    /**
     * @return \Illuminate\Contracts\Routing\UrlRoutable|mixed|null
     */
    protected function getInstance()
    {
        // We assume composition class provide a getModel() method declaration
        // If `getModel()` method does not exists, instead of failing with a BadMethodAllocation
        // We simply returns null
        try {
            $value = $this->getModel();
            $implementation = \Illuminate\Contracts\Routing\UrlRoutable::class;

            return (null === $value) || !($value instanceof $implementation) ? null : $value;
        } catch (\Exception $e) {
            trigger_error(sprintf('%s - %s', $e->getMessage(), 'Composed class required getModel() definition'), \E_USER_WARNING);

            return null;
        }
    }
}
