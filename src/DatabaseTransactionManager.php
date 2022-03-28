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

use Drewlabs\Packages\Database\Contracts\TransactionUtils;
use Drewlabs\Packages\Database\Traits\ContainerAware;

class DatabaseTransactionManager implements TransactionUtils
{
    use ContainerAware;

    /**
     * @var mixed
     */
    private $db;

    /**
     * Creates an instance of the {@link TransactionUtils} interface.
     *
     * @param mixed|null $db
     *
     * @throws \Exception
     *
     * @return self
     */
    public function __construct($db = null)
    {
        $this->db = $db ?? self::createResolver('db')();
    }

    /**
     * Start a data inserting transaction.
     *
     * @return void
     */
    public function startTransaction()
    {
        $this->db->beginTransaction();
    }

    /**
     * Commit a data inserting transaction.
     *
     * @return void
     */
    public function completeTransaction()
    {
        $this->db->commit();
    }

    /**
     * Cancel a data insertion transaction.
     *
     * @return bool
     */
    public function cancel()
    {
        $this->db->rollback();
    }

    public function runTransaction(\Closure $callback)
    {
        // Start the transaction
        $this->startTransaction();
        try {
            // Run the transaction
            $callbackResult = (new \ReflectionFunction($callback))->invoke();
            // Return the result of the transaction
            return $this->afterTransaction(static function () use ($callbackResult) {
                return $callbackResult;
            });
        } catch (\Exception $e) {
            return $this->afterCancelTransaction(static function () use ($e) {
                throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
            });
        }
    }

    private function afterTransaction(\Closure $callback)
    {
        $this->completeTransaction();

        return (new \ReflectionFunction($callback))->invoke();
    }

    private function afterCancelTransaction(\Closure $callback)
    {
        $this->cancel();

        return (new \ReflectionFunction($callback))->invoke();
    }
}
