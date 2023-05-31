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

use Drewlabs\Query\Contracts\TransactionClientInterface;
use Illuminate\Database\ConnectionInterface;

class TransactionClient implements TransactionClientInterface
{
    /**
     * Illuminate database connection instance.
     *
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * Creates an Illuminate database transaction instance.
     *
     * @return void
     */
    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    public function begin()
    {
        return $this->connection->beginTransaction();
    }

    public function commit()
    {
        return $this->connection->commit();
    }

    public function rollBack()
    {
        return $this->connection->rollBack();
    }
}
