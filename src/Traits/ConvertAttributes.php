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

use Drewlabs\Core\Helpers\Arr;

trait ConvertAttributes
{
    /**
     * Creates attributes array from mixed type.
     *
     * @param array|object $attributes
     *
     * @return array
     */
    protected function attributesToArray($attributes)
    {
        if (\is_array($attributes)) {
            return $attributes;
        }

        return Arr::create($attributes);
    }
}
