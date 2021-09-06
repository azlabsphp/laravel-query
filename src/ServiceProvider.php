<?php

namespace Drewlabs\Packages\Database;

use Drewlabs\Contracts\Data\Filters\FiltersInterface;
use Drewlabs\Contracts\Data\Parser\ModelAttributeParser as ModelAttributesParserContract;
use Drewlabs\Contracts\Hasher\IHasher;
use Drewlabs\Core\Data\Services\ModelAttributesParser;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Drewlabs\Packages\Database\Contracts\TransactionUtils;
use Drewlabs\Packages\Database\DatabaseTransactionManager;
use Drewlabs\Packages\Database\Extensions\CustomQueryCriteria;
use Illuminate\Contracts\Container\BindingResolutionException;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
        $this->bindings();
    }

    protected function bindings()
    {
        // Solve issue related to version of MySQL older than the 5.7.7 release or MariaDB older than the 10.2.2.
        $this->app['db']->connection()->getSchemaBuilder()->defaultStringLength(255);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(TransactionUtils::class, function ($app) {
            return new DatabaseTransactionManager($app->make('db'));
        });
        $this->app->bind(FiltersInterface::class, CustomQueryCriteria::class);

        $this->app->bind(ModelAttributesParserContract::class, function($app) {
            try {
                $hasher = $app->make(IHasher::class);
                return new ModelAttributesParser($hasher);
            } catch (BindingResolutionException $e) {
                $app['log']->info('IHasher interface not registered, please make sure the hasher interface is registered before proceeding!');
                return new ModelAttributesParser();
            }
        });
        
        // Register Nosql providers bindings
        $this->noSqlBindings();
    }

    /**
     * Binding for Nosql Data providers
     *
     * @return void
     */
    protected function noSqlBindings()
    {
        if (class_exists(\Drewlabs\Core\Database\NoSql\DatabaseManager::class)) {
            $this->app->bind('nosqlDb', function () {
                $manager_class = \Drewlabs\Core\Database\NoSql\DatabaseManager::class;
                new $manager_class($this->app->make('config')->get('database.nosql_driver', 'mongo'));
            });
        }
    }
}
