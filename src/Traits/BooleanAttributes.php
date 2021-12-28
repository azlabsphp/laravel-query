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

trait BooleanAttributes
{
    public function getAttribute($name)
    {
        if (
            \in_array($name, [
                'hidden',
                'is_active',
                'is_verified',
                'freezed',
                'disbaled',
            ], true) && property_exists($this, 'attributes')
        ) {
            return filter_var($this->attributes[$name] ?? null, \FILTER_VALIDATE_BOOLEAN);
        }

        return parent::getAttribute($name);
    }
}
