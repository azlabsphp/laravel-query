<?php

namespace Drewlabs\Laravel\Query;

use BadMethodCallException;
use Drewlabs\Laravel\Query\Contracts\QueryInterface;
use Drewlabs\Query\Builder;
use Illuminate\Contracts\Database\Query\Builder as BaseQueryBuilder;
use Illuminate\Support\Facades\DB;
use IteratorAggregate;

/**
 * @mixin \Drewlabs\Query\Contracts\BuilderInterface
 */
class Query implements IteratorAggregate, QueryInterface
{
    /**
     * @var Builder
     */
    private $filters;

    /**
     * @var BaseQueryBuilder
     */
    private $builder;

    /**
     * Creates new query builder instance
     */
    private function __construct(Builder $filters)
    {
        $this->filters = $filters;
    }

    /**
     * Create new Builder result instance
     * 
     * @return static 
     */
    public static function new()
    {
        return new static(Builder::new());
    }

    /**
     * Set the query builder on which the current instance executes queries
     * 
     * @param BaseQueryBuilder $table
     * 
     * @return static 
     */
    public function fromBuilder(BaseQueryBuilder $table)
    {
        $self  = clone $this;

        $self->builder = $table;
        //
        return $self;
    }

    /**
     * Set the database table on which the current instance executes queries
     * 
     * @param string $table 
     * @param string|null $as
     * 
     * @return static
     */
    public function fromTable(string $table, string $as = null)
    {
        $self  = clone $this;

        $self->builder = DB::table($table, $as);
        //
        return $self;
    }

    #[\ReturnTypeWillChange]
    public function getIterator(): \Traversable
    {
        if (null === $this->builder) {
            throw new BadMethodCallException('Query builder point to a null reference, you probably did not call fromBuilder() or fromTable() method.');
        }
        return QueryFilters::new($this->filters->getQuery() ?? [])
            ->apply($this->builder)
            ->select($this->filters->getColumns() ?? ['*'])
            ->cursor()
            ->getIterator();
    }

    public function getResult(): array
    {
        if (null === $this->builder) {
            throw new BadMethodCallException('Query builder point to a null reference, you probably did not call fromBuilder() or fromTable() method.');
        }
        return QueryFilters::new($this->filters->getQuery() ?? [])
            ->apply($this->builder)
            ->select($this->filters->getColumns() ?? ['*'])
            ->get()
            ->all();
    }

    /**
     * @internal
     * 
     * @return BaseQueryBuilder 
     */
    public function getBuilder()
    {
        return $this->builder;
    }

    /**
     * Proxy method call to filters builder
     * 
     * @param mixed $name 
     * @param mixed $arguments
     * 
     * @return $this 
     */
    public function __call($name, $arguments)
    {
        $this->filters = call_user_func_array([$this->filters, $name], $arguments);

        // Return the current object to allow method chaining on the current
        // instance
        return $this;
    }
}
