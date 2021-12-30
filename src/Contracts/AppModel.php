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

use Drewlabs\Contracts\Data\Model\ActiveModel;
use Drewlabs\Contracts\Data\Model\GuardedModel;
use Drewlabs\Contracts\Data\Model\Parseable;
use Drewlabs\Contracts\Data\Model\Relatable;

interface AppModel extends ActiveModel, Parseable, Relatable, GuardedModel
{
}