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

use Drewlabs\Query\Contracts\TransactionClientInterface;
use Drewlabs\Query\Contracts\TransactionManagerInterface;
use Drewlabs\Query\Exceptions\QueryException;

class TransactionManager implements TransactionManagerInterface
{
    /**
     * @var TransactionClientInterface
     */
    private $db;

    /**
     * Creates class instance
     * 
     * @param TransactionClientInterface $db 
     */
    public function __construct(TransactionClientInterface $db)
    {
        $this->db = $db;
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
