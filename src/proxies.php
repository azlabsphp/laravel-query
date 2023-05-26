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

namespace Drewlabs\Packages\Database\Proxy;

use Closure;
use Drewlabs\Packages\Database\SelectQueryResult;
use Drewlabs\Packages\Database\QueryLanguage;
use Drewlabs\Query\Contracts\CommandInterface;
use Drewlabs\Query\Contracts\QueryLanguageInterface;
use Drewlabs\Query\Contracts\EnumerableResultInterface;
use Drewlabs\Query\Contracts\Queryable;
use Illuminate\Database\Eloquent\Model;

use function Drewlabs\Query\Proxy\useQueryCommand;

/**
 * @param EnumerableResultInterface|mixed $value
 *
 * @return SelectQueryResult
 */
function SelectQueryResult($value)
{
    return new SelectQueryResult($value);
}

/**
 * Provides a proxy method to {@see EloquentDMLManager} class constructor.
 *
 * @param string|Queryable|Model $model
 *
 * @return QueryLanguage
 */
function DMLManager($model)
{
    return new QueryLanguage($model);
}

/**
 * High order function to apply $closure to each item
 *
 * @return \Closure
 */
function useMapQueryResult(\Closure $closure)
{
    return static function ($items) use ($closure) {
        return drewlabs_database_map_query_result($items, $closure);
    };
}

/**
 * High order function to apply a closure to a collection or list
 *
 * @return \Closure
 */
function useApplyToQueryResult(\Closure $closure)
{
    return static function ($items) use ($closure) {
        return drewlabs_database_apply($items, $closure);
    };
}

/**
 * Provides a default action handler for database queries.
 *
 * ```php
 * use function Drewlabs\Packages\Database\Proxy\useDMLQueryActionCommand;
 * use function Drewlabs\Packages\Database\Proxy\DMLManager;
 * use function Drewlabs\Packages\Database\Proxy\SelectQueryAction;
 *
 * $command = useDMLQueryActionCommand(DMLManager(Test::class));
 * // Executing command with an action using `exec` method
 * $result = $command->exec(SelectQueryAction($id));
 *
 * // or Executing command using invokable/high order function interface
 * $result = $command(SelectQueryAction($id));
 *
 *
 * // Creatating and executing action in a single line
 * useDMLQueryActionCommand(DMLManager(Test::class))(SelectQueryAction($id));
 * ```
 *
 * **Note**
 * To allow the creator function be more customizable, the function supports
 * a second parameter that allow developpers to provides their own custom action handler.
 *
 * ```php
 * use function Drewlabs\Packages\Database\Proxy\useDMLQueryActionCommand;
 * use function Drewlabs\Packages\Database\Proxy\DMLManager;
 * use use Drewlabs\Contracts\Support\Actions\Action;
 *
 * $command = useDMLQueryActionCommand(DMLManager(Test::class), function(Action $action, ?\Closure $callback = null) {
 *      // Provides custom action handlers
 * });
 * ```
 *
 * @return CommandInterface
 */
function useDMLQueryActionCommand(QueryLanguageInterface $instance, \Closure $overrides = null)
{
    return useQueryCommand($instance, $overrides);
}
