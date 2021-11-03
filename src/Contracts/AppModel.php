<?php

namespace Drewlabs\Packages\Database\Contracts;

use Drewlabs\Contracts\Data\Model\ActiveModel;
use Drewlabs\Contracts\Data\Model\GuardedModel;
use Drewlabs\Contracts\Data\Model\Parseable;
use Drewlabs\Contracts\Data\Model\Relatable;

interface AppModel extends ActiveModel, Parseable, Relatable, GuardedModel
{
}
