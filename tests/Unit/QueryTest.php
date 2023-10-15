<?php

use Drewlabs\Laravel\Query\Query;
use Drewlabs\Laravel\Query\Tests\TestCase;
use Drewlabs\Query\Builder as DrewlabsQueryBuilder;
use Drewlabs\Query\Contracts\FiltersBuilderInterface;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\LazyCollection;
use PHPUnit\Framework\MockObject\MockObject;

class QueryTest extends TestCase
{

    public function test_query_from_builder()
    {
        /**
         * @var Builder&MockObject
         */
        $builder =  $this->createMock(Builder::class);
        $query = Query::new()->fromBuilder($builder);
        $this->assertInstanceOf(Builder::class, $query->getBuilder());
    }

    public function test_query_get_iterator_throws_exception_case_from_builder_or_from_table_not_called_before()
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Query builder point to a null reference, you probably did not call fromBuilder() or fromTable() method.');
        Query::new()->and('name', 'Armani')->and('tags', 'Shoes')->getIterator();
    }

    public function test_query_get_result_throws_exception_case_from_builder_or_from_table_not_called_before()
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Query builder point to a null reference, you probably did not call fromBuilder() or fromTable() method.');
        Query::new()->and('name', 'Armani')->and('tags', 'Shoes')->getResult();
    }

    public function test_query_get_iterator_calls_builder_cursor_method_and_collection_get_iterator_method()
    {
        /**
         * @var Builder&MockObject
         */
        $builder =  $this->createMock(QueryBuilder::class);

        $builder
            ->expects($this->once())
            ->method('where')
            ->willReturn($builder);

        $builder->expects($this->once())
            ->method('select')
            ->willReturn($builder);

        $builder
            ->expects($this->once())
            ->method('whereDate')
            ->willReturn($builder);

        $builder->expects($this->once())
            ->method('cursor')
            ->willReturn(new LazyCollection([]));

        $result = Query::new()
            ->fromBuilder($builder)
            ->and('name', 'Armani')
            ->date('created_at', '2023-10-10')
            ->getIterator();

        $this->assertInstanceOf(\Traversable::class, $result);
    }

    public function test_query_get_iterator_calls_builder_cursor_method_and_collection_get_array_method()
    {
        /**
         * @var Builder&MockObject
         */
        $builder =  $this->createMock(QueryBuilder::class);

        $builder
            ->expects($this->once())
            ->method('where')
            ->willReturn($builder);

        $builder->expects($this->once())
            ->method('select')
            ->willReturn($builder);

        $builder
            ->expects($this->once())
            ->method('whereDate')
            ->willReturn($builder);

        $builder->expects($this->once())
            ->method('get')
            ->willReturn(new Collection([]));

        $result = Query::new()
            ->fromBuilder($builder)
            ->and('name', 'Armani')
            ->date('created_at', '2023-10-10')
            ->getResult();

        $this->assertTrue(is_array($result));
    }
}
