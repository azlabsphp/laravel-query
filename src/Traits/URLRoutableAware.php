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

use const E_USER_WARNING;

use Illuminate\Contracts\Routing\UrlRoutable;

trait URLRoutableAware
{
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

    public function resolveChildRouteBinding($childType, $value, $field)
    {
        if ($instance = $this->getInstance()) {
            $value = $instance->resolveChildRouteBinding($childType, $value, $field);

            return $value ? new self($value) : $value;
        }

        return null;
    }

    protected function getInstance()
    {
        try {
            return (null === $value = $this->getModel()) || !($value instanceof UrlRoutable) ? null : $value;
        } catch (\Exception $e) {
            trigger_error(sprintf('%s - %s', $e->getMessage(), 'Composed class required getModel() definition'), E_USER_WARNING);

            return null;
        }
    }
}
