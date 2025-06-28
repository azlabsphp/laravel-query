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
use Drewlabs\Laravel\Query\Tests\Stubs\Product;
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
            'aggregate' => [
                'count' => [
                    ['code', ['and' => [['published', '=', 1]]], 'published_product_count'],
                    ['code', ['and' => [['published', '=', 0]]], 'not_published_product_count']
                ],
                'sum' => [
                    [['rating', 'code'], ['and' => [['published', '=', 1]]], 'sum_published_rating'],
                    [['rating', 'code'], ['and' => [['published', '=', 0]]], 'sum_not_published_rating'],
                ],
                'min' => [
                    [['rating', 'code'], ['and' => [['published', '=', 1]]], 'min_published_rating'],
                    [['rating', 'code'], ['and' => [['published', '=', 0]]], 'min_not_published_rating'],
                ],
                'max' => [
                    [['rating', 'code'], ['and' => [['published', '=', 1]]], 'max_published_rating'],
                    [['rating', 'code'], ['and' => [['published', '=', 0]]], 'max_not_published_rating'],
                ]
            ],
        ]);

        // Act
        $result = $filters->apply(Product::query());

        
        $value = $result->get()->where(static function ($item) {
            return 'P0001' === $item->code;
        })->first();

        // Assert
        $this->assertSame(2, $value->published_product_count);
        $this->assertSame(2, $value->not_published_product_count);
        $this->assertSame(7, $value->sum_published_rating);
        $this->assertSame(3, $value->sum_not_published_rating);
        $this->assertSame(3, $value->min_published_rating);
        $this->assertSame(1, $value->min_not_published_rating);
        $this->assertSame(4, $value->max_published_rating);
        $this->assertSame(2, $value->max_not_published_rating);
    }
}
