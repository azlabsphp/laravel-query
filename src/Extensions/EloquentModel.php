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

namespace Drewlabs\Packages\Database\Extensions;

use Drewlabs\Packages\Database\Contracts\ORMModel;
use Drewlabs\Packages\Database\Traits\Model;
use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * @deprecated v3.x Use the framework ORM as base class instead and add the {@link Drewlabs\Packages\Database\Traits\Model::class} as traits.
 */
abstract class EloquentModel extends Eloquent implements ORMModel
{
    use Model;
}
