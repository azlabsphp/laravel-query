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

namespace Drewlabs\Packages\Database\Tests\Stubs;

use Drewlabs\Packages\Database\Contracts\ProvidesQueryLanguage;
use Drewlabs\Packages\Database\Tests\Stubs\Person;
use Drewlabs\Packages\Database\Traits\Queryable;

class PersonViewModelStub implements ProvidesQueryLanguage
{
    use Queryable;

    public function getModel()
    {
        return Person::class;
    }
}