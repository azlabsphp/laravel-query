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

use Drewlabs\Query\Contracts\EnumerableResultInterface;
use Drewlabs\Query\EnumerableResult;
use Illuminate\Contracts\Container\BindingResolutionException;

/**
 * @internal
 */
class SelectQueryResult
{
    /**
     * @var EnumerableResultInterface
     */
    private $value;

    /**
     * Instance initializer.
     *
     * @param mixed $value
     *
     * @return self
     */
    public function __construct($value)
    {
        $this->value = $value ?? new EnumerableResult();
    }

    /**
     * Invoke the projected function on each item of the collection.
     *
     * @throws BindingResolutionException
     *
     * @return $this
     */
    public function map(callable $callback)
    {
        $this->value = drewlabs_database_map_query_result($this->value ?? new EnumerableResult(), $callback);

        return $this;
    }

    /**
     * Invoke the projected function the collection as whole.
     *
     * @throws BindingResolutionException
     *
     * @return $this
     */
    public function all(callable $callback)
    {
        $this->value = drewlabs_database_apply($this->value ?? new EnumerableResult(), $callback);

        return $this;
    }

    /**
     * Return the wrapped query result instance.
     *
     * @return EnumerableResultInterface
     */
    public function get()
    {
        return $this->value;
    }
}
