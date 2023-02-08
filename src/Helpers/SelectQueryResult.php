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
use Drewlabs\Packages\Database\EnumerableQueryResult;
use Illuminate\Contracts\Container\BindingResolutionException;

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
     * Invoke the projected function on each item of the collection
     * 
     * @param callable $callback 
     * @return $this 
     * @throws BindingResolutionException 
     */
    public function map(callable $callback)
    {
        $this->value_ = drewlabs_database_map_query_result(
            $this->value_ ?? new EnumerableQueryResult(),
            $callback
        );

        return $this;
    }

    /**
     * Invoke the projected function the collection as whole
     * 
     * @param callable $callback 
     * @return $this 
     * @throws BindingResolutionException 
     */
    public function all(callable $callback)
    {
        $this->value_ = drewlabs_database_apply(
            $this->value_ ?? new EnumerableQueryResult(),
            $callback
        );

        return $this;
    }

    public function value()
    {
        return $this->value_;
    }
}
