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

trait HasAppendedAttributes
{
    /**
     * Set the value of the withoutAppends property.
     * 
     * @deprecated v2.6.x
     *
     * @param bool $value
     *
     * @return static
     */
    public function setWithoutAppends($value)
    {
        $this->withoutAppends = $value;

        return $this;
    }

    protected function getArrayableAppends()
    {
        $route = $this->getIndexRoute();
        if ($this->withoutAppends) {
            return null !== $route && \is_string($route) ? ['_link'] : [];
        }

        return array_merge(parent::getArrayableAppends(), isset($route) && \is_string($route) ? ['_link'] : []);
    }
}
