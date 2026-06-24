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

namespace Drewlabs\Laravel\Query\Tests\Unit;

use Closure;

interface TestQueryBuilderInterface
{
    /**
     * @param mixed $column 
     * @param mixed $operator 
     * @param mixed $value 
     * @return mixed 
     */
    public function where($column, $operator = null, $value = null);

    /**
     * @param mixed $column 
     * @param array $values 
     * @return mixed 
     */
    public function whereIn($column, array $values);

    /**
     * @param mixed $relation 
     * @param string $operator 
     * @param int $count 
     * @param string $boolean 
     * @param null|Closure $callback 
     * @return mixed 
     */
    public function has($relation, string $operator = '>=', int $count = 1, $boolean = 'and', ?\Closure $callback = null);

    /**
     * @param mixed $relations 
     * @param mixed $column 
     * @param mixed $function 
     * @return mixed 
     */
    public function withAggregate($relations, $column, $function = null);

    public function clone();

    /**
     * @param mixed $expression 
     * @param mixed $bindings 
     * @return mixed 
     */
    public function selectRaw($expression, $bindings = null);

    public function limit(int $limit);

    /**
     * 
     * @param string|string[] $column
     * 
     * @return mixed 
     */
    public function addSelect($column);
}
