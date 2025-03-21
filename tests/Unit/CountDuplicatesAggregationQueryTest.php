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

use Drewlabs\Laravel\Query\QueryFilters;
use Drewlabs\Laravel\Query\Tests\Stubs\Person;
use Drewlabs\Laravel\Query\Tests\TestCase;

/*
 * This file is part of the drewlabs namespace.
 *
 * (c) Sidoine Azandrew <azandrewdevelopper@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class CountDuplicatesAggregationQueryTest extends TestCase
{
    public function test_query_filters_count_by()
    {
        $filters = new QueryFilters([
            'aggregate' => ['addCount' => [['age', null, 'age_count']]],
        ]);

        // Act
        $result = $filters->apply(Person::query());
        $match = $result->get()->where(static function ($item) {
            return 'benjaminpayaro@gmail.com' === $item->email;
        })->first();

        // Assert
        $this->assertSame(2, $match->age_count);
    }
}
