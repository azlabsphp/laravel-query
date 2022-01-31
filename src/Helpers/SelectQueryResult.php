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

namespace Drewlabs\Packages\Database\Helpers;

use Drewlabs\Contracts\Data\EnumerableQueryResult as ContractsEnumerableQueryResult;
use Drewlabs\Core\Data\EnumerableQueryResult;

class SelectQueryResult
{
    /**
     * @var ContractsEnumerableQueryResult|mixed
     */
    private $value_;

    /**
     * Instance initializer.
     *
     * @param mixed $value
     *
     * @return self
     */
    public function __construct($value)
    {
        $this->value_ = $value ?? new EnumerableQueryResult();
    }

    /**
     * Apply an aggregation callback on each item of the result query.
     *
     * @return self
     */
    public function each(callable $callback)
    {
        $this->value_ = drewlabs_database_map_query_result($this->value_ ?? new EnumerableQueryResult(), $callback);

        return $this;
    }

    /**
     * Apply an aggregation callback to a batch query result.
     *
     * @return self
     */
    public function all(callable $callback)
    {
        $this->value_ = drewlabs_database_apply($this->value_ ?? new EnumerableQueryResult(), $callback);

        return $this;
    }

    public function value()
    {
        return $this->value_;
    }
}
