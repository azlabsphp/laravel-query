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

namespace Drewlabs\LaravelQuery;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->bindings();
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
    }

    protected function bindings()
    {
        // Solve issue related to version of MySQL older than the 5.7.7 release or MariaDB older than the 10.2.2.
        $this->app['db']->connection()->getSchemaBuilder()->defaultStringLength(255);
    }
}
