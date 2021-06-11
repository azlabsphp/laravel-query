<?php

namespace Drewlabs\Packages\Database;

use Drewlabs\Packages\Database\Contracts\TransactionUtils as ContractsTransactionUtils;
use Illuminate\Container\Container;
use Psr\Container\ContainerInterface;
class DataTransactionUtils implements ContractsTransactionUtils
{
    /**
     *
     * @var Container
     */
    private $app;


    /**
     * Database utilities provider
     *
     * @param Container $app
     */
    public function __construct(ContainerInterface $app = null)
    {
        $this->app = $app ?? Container::getInstance();
    }

    /**
     * Start a data inserting transaction
     *
     * @return void
     */
    public function startTransaction()
    {
        $this->app['db']->beginTransaction();
    }
    /**
     * Commit a data inserting transaction
     *
     * @return void
     */
    public function completeTransaction()
    {
        $this->app['db']->commit();
    }
    /**
     * Cancel a data insertion transaction
     *
     * @return boolean
     */
    public function cancel()
    {
        $this->app['db']->rollback();
    }
}
