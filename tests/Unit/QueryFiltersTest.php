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


    public function test_query_filters_aggregate_call_with_with_aggregate_if_aggregation_method_is_a_two_parameter_array()
    {
        /**
         * @var MockObject&TestQueryBuilderInterface
         */
        $mockObject = $this->createMock(TestQueryBuilderInterface::class);
        $queryFilters = CreateQueryFilters([
            'aggregate' => [
                'min' => [['grades', 'courses']]
            ]
        ]);

        $mockObject
            ->expects($this->exactly(1))
            ->method('withAggregate')
            ->with('courses', 'grades')
            ->willReturn($mockObject);

        $this->assertSame($mockObject, $queryFilters->apply($mockObject));
    }

    public function test_query_filters_aggregate_min_call_add_select_if_aggregation_method_is_called_string_as_column_or_list_of_string_as_column()
    {
        /**
         * @var MockObject&TestQueryBuilderInterface
         */
        $mockObject = $this->createMock(TestQueryBuilderInterface::class);
        $queryFilters = CreateQueryFilters([
            'aggregate' => [
                'min' => ['grades', 'courses']
            ]
        ]);

        $mockObject
        ->expects($this->exactly(2))
        ->method('min')
        ->with(...static::withConsecutive(['grades'], ['courses']))
        ->willReturn($mockObject);

        $mockObject
            ->expects($this->exactly(2))
            ->method('clone')
            ->willReturn($mockObject);

        $mockObject
            ->expects($this->exactly(2))
            ->method('addSelect')
            ->with(...static::withConsecutive([['grades_min' => $mockObject]], [['courses_min' => $mockObject]]))
            ->willReturn($mockObject);

        $this->assertSame($mockObject, $queryFilters->apply($mockObject));
    }

    public function test_query_filters_aggregate_max_call_add_select_if_aggregation_method_is_called_string_as_column_or_list_of_string_as_column()
    {
        /**
         * @var MockObject&TestQueryBuilderInterface
         */
        $mockObject = $this->createMock(TestQueryBuilderInterface::class);
        $queryFilters = CreateQueryFilters([
            'aggregate' => [
                'max' => ['grades', 'courses']
            ]
        ]);

        $mockObject
        ->expects($this->exactly(2))
        ->method('max')
        ->with(...static::withConsecutive(['grades'], ['courses']))
        ->willReturn($mockObject);

        $mockObject
            ->expects($this->exactly(2))
            ->method('clone')
            ->willReturn($mockObject);

        $mockObject
            ->expects($this->exactly(2))
            ->method('addSelect')
            ->with(...static::withConsecutive([['grades_max' => $mockObject]], [['courses_max' => $mockObject]]))
            ->willReturn($mockObject);

        $this->assertSame($mockObject, $queryFilters->apply($mockObject));
    }

    

    public function test_query_filters_aggregate_avg_call_add_select_if_aggregation_method_is_called_string_as_column_or_list_of_string_as_column()
    {
        /**
         * @var MockObject&TestQueryBuilderInterface
         */
        $mockObject = $this->createMock(TestQueryBuilderInterface::class);
        $queryFilters = CreateQueryFilters([
            'aggregate' => [
                'avg' => ['grades', 'courses']
            ]
        ]);

        $mockObject
        ->expects($this->exactly(2))
        ->method('avg')
        ->with(...static::withConsecutive(['grades'], ['courses']))
        ->willReturn($mockObject);

        $mockObject
            ->expects($this->exactly(2))
            ->method('clone')
            ->willReturn($mockObject);

        $mockObject
            ->expects($this->exactly(2))
            ->method('addSelect')
            ->with(...static::withConsecutive([['grades_avg' => $mockObject]], [['courses_avg' => $mockObject]]))
            ->willReturn($mockObject);

        $this->assertSame($mockObject, $queryFilters->apply($mockObject));
    }

    public function test_query_filters_aggregate_sum_call_add_select_if_aggregation_method_is_called_string_as_column_or_list_of_string_as_column()
    {
        /**
         * @var MockObject&TestQueryBuilderInterface
         */
        $mockObject = $this->createMock(TestQueryBuilderInterface::class);
        $queryFilters = CreateQueryFilters([
            'aggregate' => [
                'sum' => ['grades', 'courses']
            ]
        ]);

        $mockObject
        ->expects($this->exactly(2))
        ->method('sum')
        ->with(...static::withConsecutive(['grades'], ['courses']))
        ->willReturn($mockObject);

        $mockObject
            ->expects($this->exactly(2))
            ->method('clone')
            ->willReturn($mockObject);

        $mockObject
            ->expects($this->exactly(2))
            ->method('addSelect')
            ->with(...static::withConsecutive([['grades_sum' => $mockObject]], [['courses_sum' => $mockObject]]))
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
        $queryFilters->apply(new class($this)
        {
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
