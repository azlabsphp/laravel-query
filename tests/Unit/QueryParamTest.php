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

namespace Drewlabs\Packages\Database\Tests\Unit;

use function Drewlabs\Packages\Database\Proxy\QueryParam;
use Drewlabs\Packages\Database\QueryParamsObject;
use Drewlabs\Packages\Database\Tests\Stubs\Person;

use Drewlabs\Packages\Database\Tests\TestCase;

class QueryParamTest extends TestCase
{
    public function test_contructor()
    {
        $object = QueryParam([
            'model' => Person::class,
            'column' => 'firstname',
        ]);
        $this->assertInstanceOf(QueryParamsObject::class, $object);
    }

    public function test_constructor_throws_exception()
    {
        $this->expectException(\InvalidArgumentException::class);
        new QueryParamsObject([
            'model' => null,
            'column' => 'firstname',
        ]);
    }
}
