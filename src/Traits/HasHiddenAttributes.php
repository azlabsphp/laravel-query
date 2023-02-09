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

/**
 * @deprecated v3.x Not needed for every application models therefore will
 * be removed in future release. Provides an implementation in a data transfert
 * object instead
 */
trait HasHiddenAttributes
{
    /**
     * Returns the attached model hidden property.
     *
     * @return array
     */
    public function getHidden()
    {
        return $this->hidden;
    }

    /**
     * Set the attached model hidden property.
     *
     * @return static
     */
    public function setHidden(array $attributes)
    {
        $this->hidden = $attributes;

        return $this;
    }
}
