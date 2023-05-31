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

namespace Drewlabs\Laravel\Query\Tests\Unit;

use Drewlabs\Laravel\Query\Tests\Stubs\ModelValue;
use Drewlabs\Laravel\Query\Tests\TestCase;

class URLRoutableTest extends TestCase
{
    public function test_get_route_key()
    {
        $this->assertSame((new ModelValue())->getRouteKey(), null);
    }

    public function test_get_route_key_name()
    {
        $this->assertSame((new ModelValue())->getRouteKeyName(), 'id');
    }

    public function test_resolve_route_bindings()
    {
        $value = (new ModelValue())->resolveRouteBinding(1);
        $this->assertInstanceOf(ModelValue::class, $value);
    }
}
