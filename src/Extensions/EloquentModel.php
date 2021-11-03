<?php

namespace Drewlabs\Packages\Database\Extensions;

use Drewlabs\Packages\Database\Contracts\AppModel;
use Drewlabs\Packages\Database\Traits\Model;
use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Use the framework ORM as base class instead and add the {@link Drewlabs\Packages\Database\Traits\Model::class} as traits
 * 
 * @deprecated v3.1
 */
abstract class EloquentModel extends Eloquent implements AppModel
{
    use Model;
}
