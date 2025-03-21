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

namespace Drewlabs\Laravel\Query;

use Drewlabs\Laravel\Query\Contracts\QueryInterface;
use Drewlabs\Query\Builder;
use Illuminate\Contracts\Database\Query\Builder as BaseQueryBuilder;
use Illuminate\Support\Facades\DB;

/**
 * @mixin \Drewlabs\Query\Contracts\BuilderInterface
 */
class Query implements \IteratorAggregate, QueryInterface
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
     * Creates new query builder instance.
     */
    private function __construct(Builder $filters)
    {
        $this->filters = $filters;
    }

    /**
     * Proxy method call to filters builder.
     *
     * @param mixed $name
     * @param mixed $arguments
     *
     * @return $this
     */
    public function __call($name, $arguments)
    {
        $this->filters = \call_user_func_array([$this->filters, $name], $arguments);

        // Return the current object to allow method chaining on the current
        // instance
        return $this;
    }

    /**
     * Create new Builder result instance.
     *
     * @return static
     */
    public static function new()
    {
        return new static(Builder::new());
    }

    /**
     * Set the query builder on which the current instance executes queries.
     *
     * @return static
     */
    public function fromBuilder(BaseQueryBuilder $table): self
    {
        $self = clone $this;

        $self->builder = $table;

        return $self;
    }

    /**
     * Set the database table on which the current instance executes queries.
     *
     * @return static
     */
    public function fromTable(string $table, ?string $as = null): self
    {
        return $this->from($table, $as);
    }

    /**
     * defines database table object from which query is builded.
     *
     * @return static
     */
    public function from(string $table, ?string $as = null): self
    {
        $self = clone $this;

        $self->builder = DB::table($table, $as);

        return $self;
    }

    #[\ReturnTypeWillChange]
    public function getIterator(): \Traversable
    {
        if (null === $this->builder) {
            throw new \BadMethodCallException('Query builder point to a null reference, you probably did not call fromBuilder() or fromTable() method.');
        }

        return $this->prepareQuery()->cursor()->getIterator();
    }

    public function getResult(): array
    {
        if (null === $this->builder) {
            throw new \BadMethodCallException('Query builder point to a null reference, you probably did not call fromBuilder() or fromTable() method.');
        }

        return $this->prepareQuery()->get()->all();
    }

    /**
     * Returns the first entry matching the query.
     *
     * @throws \BadMethodCallException
     * @throws \InvalidArgumentException
     *
     * @return object|null
     */
    public function first()
    {
        if (null === $this->builder) {
            throw new \BadMethodCallException('Query builder point to a null reference, you probably did not call fromBuilder() or fromTable() method.');
        }

        // Fetch the first result of the prepareQuery method
        return $this->prepareQuery()->first();
    }

    /**
     * Get the count of the query result rows.
     *
     * @param array $columns
     *
     * @throws \BadMethodCallException
     */
    public function count($columns = ['*']): int
    {
        if (null === $this->builder) {
            throw new \BadMethodCallException('Query builder point to a null reference, you probably did not call fromBuilder() or fromTable() method.');
        }

        return $this->prepareQuery()->count($columns);
    }

    /**
     * Get the minimum value for the given column in the query result.
     *
     * @throws \BadMethodCallException
     *
     * @return mixed
     */
    public function min(string $column)
    {
        if (null === $this->builder) {
            throw new \BadMethodCallException('Query builder point to a null reference, you probably did not call fromBuilder() or fromTable() method.');
        }

        return $this->prepareQuery()->min($column);
    }

    /**
     * Get the maximum value for the given column in the query result.
     *
     * @throws \BadMethodCallException
     *
     * @return mixed
     */
    public function max(string $column)
    {
        if (null === $this->builder) {
            throw new \BadMethodCallException('Query builder point to a null reference, you probably did not call fromBuilder() or fromTable() method.');
        }

        return $this->prepareQuery()->max($column);
    }

    /**
     * Get the sum of all values for the given column in the query result.
     *
     * @throws \BadMethodCallException
     *
     * @return mixed
     */
    public function sum(string $column)
    {
        if (null === $this->builder) {
            throw new \BadMethodCallException('Query builder point to a null reference, you probably did not call fromBuilder() or fromTable() method.');
        }

        return $this->prepareQuery()->sum($column);
    }

    /**
     * Get the average value for the given column in the query result.
     *
     * @throws \BadMethodCallException
     *
     * @return mixed
     */
    public function avg(string $column)
    {
        if (null === $this->builder) {
            throw new \BadMethodCallException('Query builder point to a null reference, you probably did not call fromBuilder() or fromTable() method.');
        }

        return $this->prepareQuery()->avg($column);
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
     * Prepare base query builder.
     *
     * @return BaseQueryBuilder
     */
    private function prepareQuery()
    {
        return QueryFilters::new($this->filters->getQuery() ?? [])
            ->apply($this->builder)
            ->select(!empty($columns = $this->filters->getColumns()) ? $columns : ['*']);
    }
}
