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
    public function where($column, $operator = null, $value = null);

    public function whereIn($column, array $values);

    public function has($relation, string $operator = '>=', int $count = 1, $boolean = 'and', Closure $callback = null);
}
