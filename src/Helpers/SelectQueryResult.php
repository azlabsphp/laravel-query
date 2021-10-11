<?php

namespace Drewlabs\Packages\Database\Helpers;

use Drewlabs\Contracts\Data\DataProviderQueryResultInterface;
use Drewlabs\Core\Data\DataProviderQueryResult;
use Illuminate\Contracts\Pagination\Paginator;

class SelectQueryResult
{
    /**
     *
     * @var Paginator|DataProviderQueryResultInterface
     */
    private $value_;

    /**
     * Instance initializer
     *
     * @param Paginator|DataProviderQueryResultInterface $value
     * @return self
     */
    public function __construct($value)
    {
        $this->value_ = $value ?? new DataProviderQueryResult();
    }

    /**
     * Apply an aggregation callback on each item of the result query
     *
     * @param callable $callback
     * @return self
     */
    public function each(callable $callback)
    {
        $this->value_ = drewlabs_database_map_query_result($this->value_ ?? new DataProviderQueryResult(), $callback);
        return $this;
    }

    /**
     * Apply an aggregation callback to a batch query result
     *
     * @param callable $callback
     * @return self
     */
    public function all(callable $callback)
    {
        $this->value_ = drewlabs_database_apply($this->value_ ?? new DataProviderQueryResult(), $callback);
        return $this;
    }

    public function value()
    {
        return $this->value_;
    }
}
