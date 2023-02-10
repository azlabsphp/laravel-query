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

namespace Drewlabs\Packages\Database\Contracts;

interface TransactionManagerInterface
{
    /**
     * Run an operation as transaction which automatically rollback
     * if the operation does not completed successfully or commit
     * in the other case.
     *
     * @param \Closure $callback PHP callback to invoke as transaction operation
     *
     * @return T
     */
    public function transaction(\Closure $callback);
}
