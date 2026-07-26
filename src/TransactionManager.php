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
use Drewlabs\Query\Contracts\TransactionManagerInterface;
use Drewlabs\Query\Exceptions\QueryException;
use Illuminate\Database\QueryException as BaseQueryException;

class TransactionManager implements TransactionManagerInterface
{
    /**
     * @var TransactionClientInterface
     */
    private $db;

    /**
     * Creates class instance.
     */
    public function __construct(TransactionClientInterface $db)
    {
        $this->db = $db;
    }

    public function transaction(\Closure $callback)
    {
        try {
            $this->db->begin();

            $callbackResult = (new \ReflectionFunction($callback))->invoke();

            return $this->afterTransaction(static function () use ($callbackResult) {
                return $callbackResult;
            });
        } catch (\Exception $e) {
            return $this->afterCancelTransaction(static function () use ($e) {

                // we cast eloquent query exception class into query exception defined in the current package if it was thrown
                if ($e instanceof BaseQueryException) {
                    throw new QueryException($e->getMessage(), $e->getCode(), $e);
                }
                
                throw $e;
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
