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

namespace Drewlabs\Packages\Database;

use Drewlabs\Packages\Database\Contracts\TransactionClientInterface;
use Drewlabs\Packages\Database\Contracts\TransactionManagerInterface;
use Drewlabs\Packages\Database\Eloquent\TransactionClient;
use Drewlabs\Packages\Database\Exceptions\QueryException;
use Illuminate\Database\Eloquent\Model;

class TransactionManager implements TransactionManagerInterface
{
    /**
     * @var TransactionClientInterface
     */
    private $db;

    /**
     * Creates a database transaction management instance.
     *
     * @throws \Exception
     *
     * @return self
     */
    public function __construct(TransactionClientInterface $db)
    {
        $this->db = $db;
    }

    /**
     * Creates a new instance from a model instance.
     *
     * @return static
     */
    public static function new($arg)
    {
        if ($arg instanceof Model) {
            return new static(new TransactionClient($arg->getConnection()));
        }
        if ($arg instanceof TransactionClientInterface) {
            return new static($arg);
        }
        throw new \InvalidArgumentException('Cannot build a transaction manager from the provided instance, '.\gettype($arg));
    }

    public function transaction(\Closure $callback)
    {
        try {
            // Start the transaction
            $this->db->begin();
            // Run the transaction
            $callbackResult = (new \ReflectionFunction($callback))->invoke();
            // Return the result of the transaction
            return $this->afterTransaction(static function () use ($callbackResult) {
                return $callbackResult;
            });
        } catch (\Exception $e) {
            return $this->afterCancelTransaction(static function () use ($e) {
                throw new QueryException($e->getMessage(), $e->getCode(), $e);
            });
        }
    }

    private function afterTransaction(\Closure $callback)
    {
        $this->db->commit();

        return $callback();
    }

    private function afterCancelTransaction(\Closure $callback)
    {
        $this->db->rollback();

        return $callback();
    }
}
