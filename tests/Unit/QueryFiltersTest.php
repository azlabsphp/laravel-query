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

use Drewlabs\Laravel\Query\Tests\Unit\TestQueryBuilderInterface;
use Drewlabs\Laravel\Query\Tests\Unit\WithConsecutiveCalls;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use function Drewlabs\Laravel\Query\Proxy\CreateQueryFilters;

// 'or' => ['lastname', 'like', '%AZOMEDOH%'],
// 'exists' => [
//     'column' => 'addresses',
//     'match' => [
//         'method' => 'where',
//         'params' => ['email', 'like', '%azandrew@%'],
//     ],
// ],
// 'in' => ['likes', [10, 12, 2]],
// 'notin' => ['name', ['Milick', 'Jonh Doe']],
// 'notnull' => 'firstname',
// 'isnull' => 'lastname',
// 'sort' => ['id'],
class QueryFiltersTest extends TestCase
{
    use WithConsecutiveCalls;

    public function test_filters_apply_call_builder_where_method_with_provided_parameters()
    {
        /**
         * @var MockObject
         */
        $mockObject = $this->createMock(TestQueryBuilderInterface::class);
        $queryFilters = CreateQueryFilters(['where' => ['age', 28]]);

        $mockObject
            ->expects($this->once(2))
            ->method('where')
            ->with('age', 28)
            ->willReturn($mockObject);

        $this->assertSame($mockObject, $queryFilters->apply($mockObject));
    }

    public function test_filters_call_invoke_builder_twice_whith_2_queries_as_arguments()
    {
        /**
         * @var MockObject
         */
        $mockObject = $this->createMock(TestQueryBuilderInterface::class);
        $queryFilters = CreateQueryFilters(['where' => [['age', 28], ['name', 'like', '%azandrew%']]]);

        $mockObject
            ->expects($this->exactly(2))
            ->method('where')
            ->with(...static::withConsecutive(['age', 28, null], ['name', 'like', '%azandrew%']))
            ->willReturn($mockObject);

        $this->assertSame($mockObject, $queryFilters->apply($mockObject));
    }

    public function test_filters_call_invoke_builder_with_closure()
    {
        /**
         * @var MockObject
         */
        $mockObject = $this->createMock(TestQueryBuilderInterface::class);
        $queryFilters = CreateQueryFilters(['where' => ['method' => 'in', 'params' => ['ratings', [3, 5]]]]);

        $mockObject
            ->expects($this->any())
            ->method('where')
            ->with(static function () {
            })
            ->willReturn($mockObject);

        // Test the mock is invoked with a closure whe  apply is called
        $this->assertSame($mockObject, $queryFilters->apply($mockObject));

        // Use a mock to test invocation of sub queries
        $queryFilters->apply(new class($this) {
            private $testObject;

            public function __construct(TestCase $testObject)
            {
                $this->testObject = $testObject;
            }

            public function where(Closure $closure)
            {
                return $closure($this);
            }

            public function whereIn($column, $values)
            {
                $this->testObject->assertEquals(['ratings', [3, 5]], [$column, $values]);

                return $this;
            }
        });
    }
}
