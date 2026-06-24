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

use Illuminate\Database\Eloquent\Concerns\HasUuids as EloquentHasUuids;

/** @deprecated v0.4.x use the implementation that is provided by the framework */
trait HasUuids
{
    use EloquentHasUuids;
}
