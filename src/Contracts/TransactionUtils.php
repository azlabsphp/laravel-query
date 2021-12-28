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

interface TransactionUtils
{
    /**
     * Start a transaction.
     *
     * @return void
     */
    public function startTransaction();

    /**
     * Commit a transaction.
     *
     * @return void
     */
    public function completeTransaction();

    /**
     * Cancel transaction.
     *
     * @return bool
     */
    public function cancel();
}
