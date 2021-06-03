<?php

namespace Drewlabs\Packages\Database;

use Drewlabs\Contracts\Data\ModelFiltersInterface;
use Drewlabs\Contracts\Data\Parser\ModelAttributeParser;
use Drewlabs\Core\Data\Services\ModelAttributesParser;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Drewlabs\Packages\Database\Contracts\TransactionUtils;
use Drewlabs\Packages\Database\DataTransactionUtils;
use Drewlabs\Packages\Database\Extensions\CustomQueryCriteria;

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
            return new DataTransactionUtils($app);
        });
        $this->app->bind(ModelFiltersInterface::class, CustomQueryCriteria::class);

        $this->app->bind(ModelAttributeParser::class, ModelAttributesParser::class);
        
        # region - Must be remove in v4.0
        $this->app->bind(\Drewlabs\Contracts\Data\IModelFilter::class, function ($app) {
            return new CustomQueryCriteria();
        });

        $this->app->bind(\Drewlabs\Contracts\Data\DataRepository\Services\IModelAttributesParser::class, function ($app) {
            return new ModelAttributesParser($app[\Drewlabs\Contracts\Hasher\IHasher::class]);
        });
        # endregion - Must be remove in v4.0
        
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
