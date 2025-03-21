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

namespace Drewlabs\Laravel\Query\Contracts;

interface QueryInterface
{
    /**
     * Get query results as array.
     *
     * @throws \BadMethodCallException
     */
    public function getResult(): array;
}
