<?php

namespace Drewlabs\Packages\Database\Extensions;

use Drewlabs\Contracts\Data\Model\ActiveModel;
use Drewlabs\Contracts\Data\Model\GuardedModel;
use Drewlabs\Contracts\Data\Model\Parseable;
use Drewlabs\Contracts\Data\Model\Relatable;
use Drewlabs\Packages\Database\Traits\Model;
use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Use the framework ORM as base class instead and add the {@link Drewlabs\Packages\Database\Traits\Model::class} as traits
 * 
 * @deprecated v3.1
 */
abstract class EloquentModel extends Eloquent implements ActiveModel, Parseable, Relatable, GuardedModel
{
    use Model;
}
