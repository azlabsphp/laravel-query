<?php

namespace Drewlabs\Packages\Database;

use Drewlabs\Packages\Database\Contracts\TransactionUtils as ContractsTransactionUtils;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use ReflectionFunction;

class DatabaseTransactionManager implements ContractsTransactionUtils
{
    /**
     *
     * @var mixed
     */
    private $db;

    /**
     * Creates an instance of the {@link TransactionUtils} interface
     * 
     * @param mixed|null $db 
     * @return self 
     * @throws BindingResolutionException 
     */
    public function __construct($db = null)
    {
        $this->db = $db ?? Container::getInstance()->make('db');
    }

    /**
     * Start a data inserting transaction
     *
     * @return void
     */
    public function startTransaction()
    {
        $this->db->beginTransaction();
    }
    /**
     * Commit a data inserting transaction
     *
     * @return void
     */
    public function completeTransaction()
    {
        $this->db->commit();
    }
    /**
     * Cancel a data insertion transaction
     *
     * @return boolean
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
            $callbackResult = (new ReflectionFunction($callback))->invoke();
            // Return the result of the transaction
            return $this->afterTransaction(function () use ($callbackResult) {
                return $callbackResult;
            });
        } catch (\Exception $e) {
            return $this->afterCancelTransaction(function () use ($e) {
                throw new \RuntimeException($e);
            });
        }
    }

    private function afterTransaction(\Closure $callback)
    {
        $this->completeTransaction();
        return (new ReflectionFunction($callback))->invoke();
    }

    private function afterCancelTransaction(\Closure $callback)
    {
        $this->cancel();
        return (new ReflectionFunction($callback))->invoke();
    }
}
