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

use function Drewlabs\Packages\Database\Proxy\DMLManager;
use Drewlabs\Packages\Database\QueryFiltersBuilder;
use Drewlabs\Packages\Database\Tests\Stubs\Person;
use Drewlabs\Packages\Database\Tests\Stubs\ViewModel;

use Drewlabs\Packages\Database\Tests\TestCase;

class QueryFiltersBuilderTest extends TestCase
{
    public function test_build_from_query_parameters()
    {
        $filters = QueryFiltersBuilder::filtersFromQueryParameters(new Person(), new class() {
            private $inputs = [
                'lastname' => 'Azandrew',
                'age' => 29,
                'addresses__email' => 'azandrewdevelopper@gmail.com',
            ];
            use ViewModel;
        });
        $this->assertTrue('addresses' === $filters['whereHas'][0][0]);
        $this->assertInstanceOf(\Closure::class, $filters['whereHas'][0][1]);
        $this->assertSame($filters['orWhere'][0], ['lastname', 'like', '%Azandrew%']);
    }

    public function test_build_from_query_input()
    {
        $filters = QueryFiltersBuilder::filterFrom__Query(new class() {
            private $inputs = [
                '_query' => [
                    'where' => ['age', 28],
                    'orWhere' => ['lastname', 'like', '%AZOMEDOH%'],
                    'whereHas' => [
                        'column' => 'addresses',
                        'match' => [
                            'method' => 'where',
                            'params' => ['email', 'like', '%azandrew@%'],
                        ],
                    ],
                    'orderBy' => ['id'],
                ],
            ];
            use ViewModel;
        });
        $this->assertTrue('addresses' === $filters['whereHas'][0]);
        $this->assertInstanceOf(\Closure::class, $filters['whereHas'][1]);
        $this->assertSame($filters['orWhere'], ['lastname', 'like', '%AZOMEDOH%']);
    }

    public function test_build_method()
    {
        $filters = QueryFiltersBuilder::for(new Person())->build(new class() {
            private $inputs = [
                'firstname' => 'SIDOINE',
                'age' => '20',
                '_query' => [
                    // 'where' => ['age', 28],
                    'orWhere' => ['lastname', 'like', '%AZOMEDOH%'],
                    'whereHas' => [
                        'column' => 'addresses',
                        'match' => [
                            'method' => 'where',
                            'params' => ['email', 'like', '%azandrew@%'],
                        ],
                    ],
                    'orderBy' => ['id'],
                ],
            ];
            use ViewModel;
        });
        $result = DMLManager(Person::class)->select($filters);
        $this->assertNotNull($result);
        $this->assertSame('azandrew@liksoft.tg', $result->first()->addresses->first()->email);
    }

    public function test_filter_query_parameters_returns_where_clauses_if_value_contains_and_operator()
    {
        $result = QueryFiltersBuilder::filtersFromQueryParameters(new Person(), $this->createParametersBag([
            'email' => '&&:==:azandrewdevelopper@gmail.com',
            'lastname' => 'and:=like:AZOMEDOH',
            'age' => '&&:>=:2022-10-10|&&:<=:2022-10-10',
        ]));
        $this->assertTrue(($result['where'] ?? null) !== null);
        $this->assertSame(['email', '=', 'azandrewdevelopper@gmail.com'], $result['where'][0]);
        $this->assertSame(['lastname', 'like', '%AZOMEDOH%'], $result['where'][1]);
    }

    public function test_build_query_filters_with_default_parameters()
    {
        $query = new class() {
            public function __invoke($query)
            {
                return $query->where('url', 'http://localhost:8000/pictures/1665418738634445f249513042648693');
            }
        };
        $result = QueryFiltersBuilder::for(new Person())->build(
            $this->createParametersBag(
                [
                    'email' => '&&:==:azandrewdevelopper@gmail.com',
                    'lastname' => 'and:=like:AZOMEDOH',
                    '_query' => [
                        'whereHas' => [
                            'column' => 'addresses',
                            'match' => [
                                'method' => 'where',
                                'params' => ['email', 'like', '%azandrew@%'],
                            ],
                        ],
                        'orderBy' => ['id'],
                    ],
                ]
            ),
            [
                'whereHas' => ['profile', $query],
                'where' => ['age', 28],
            ]
        );
        $this->assertTrue(($result['whereHas'] ?? null) !== null);
        $this->assertSame(['profile', $query], $result['whereHas'][0]);
        $this->assertSame(['profile', $query], $result['whereHas'][0]);
    }

    private function createParametersBag(array $inputs)
    {
        return new class($inputs) {
            /**
             * Parameters.
             *
             * @var array
             */
            private $values = [];

            /**
             * @return void
             */
            public function __construct(array $values)
            {
                $this->values = $values;
            }

            public function has(string $name)
            {
                return \in_array($name, array_keys($this->values), true);
            }

            public function get(string $name)
            {
                return $this->values[$name] ?? null;
            }

            public function all()
            {
                return $this->values;
            }
        };
    }
}
