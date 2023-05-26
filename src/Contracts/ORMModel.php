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

namespace Drewlabs\Packages\Database\Contracts;

use Drewlabs\Contracts\Data\Model\GuardedModel;
use Drewlabs\Contracts\Data\Model\HasRelations;
use Drewlabs\Contracts\Data\Model\HidesAttributes;
use Drewlabs\Contracts\Data\Model\Model;
use Drewlabs\Contracts\Data\Model\Parseable;
use Drewlabs\Query\Contracts\Queryable;

/**
 * Interface definition arround methods provided by Laravel Eloquent ORM
 * along with some useful method.
 */
interface ORMModel extends Model, HidesAttributes, Parseable, HasRelations, GuardedModel, Queryable
{
}
