<?php

namespace Drewlabs\Packages\Database\Contracts;

use Drewlabs\Contracts\Data\Model\Model;
use Drewlabs\Contracts\Data\Model\GuardedModel;
use Drewlabs\Contracts\Data\Model\HasRelations;
use Drewlabs\Contracts\Data\Model\HidesAttributes;
use Drewlabs\Contracts\Data\Model\Parseable;

/**
 * Interface definition arround methods provided by Laravel Eloquent ORM
 * along with some useful method 
 * 
 * @package Drewlabs\Packages\Database\Contracts
 */
interface ORMModel extends
    Model,
    HidesAttributes,
    Parseable,
    HasRelations,
    GuardedModel
{
}
