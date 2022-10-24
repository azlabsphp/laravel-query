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
use Drewlabs\Contracts\Data\DML\DMLProvider;
use Drewlabs\Contracts\Data\EnumerableQueryResult;
use Drewlabs\Contracts\Data\ModelFiltersInterface;
use Drewlabs\Contracts\Support\Actions\Action as ActionsInterface;
use Drewlabs\Contracts\Support\Actions\ActionPayload as ActionPayloadInterface;
use Drewlabs\Contracts\Support\Actions\ActionResult as ActionResultInterface;
use Drewlabs\Packages\Database\Contracts\DMLQueryCommandInterface;
use Drewlabs\Packages\Database\EloquentBuilderQueryFilters;
use Drewlabs\Packages\Database\EloquentDMLManager;
use Drewlabs\Packages\Database\Exceptions\InvalidDMLQueryActionException;
use Drewlabs\Packages\Database\Helpers\SelectQueryResult;
use Drewlabs\Packages\Database\QueryParamsObject;
use Drewlabs\Support\Actions\Action;

use function Drewlabs\Support\Proxy\Action;
use function Drewlabs\Support\Proxy\ActionResult;

/**
 * @param EnumerableQueryResult|mixed $value
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
 * @param string|object $model
 *
 * @return EloquentDMLManager
 */
function DMLManager($model)
{
    return new EloquentDMLManager(\is_string($model) ? $model : \get_class($model));
}

/**
 * Provides a proxy method to {@see ModelFiltersInterface} implementor class.
 *
 * @return ModelFiltersInterface
 */
function ModelFiltersHandler(array $queries = [])
{
    return new EloquentBuilderQueryFilters($queries ?? []);
}

/**
 * Create a query parameter object.
 *
 * @return QueryParamsObject
 */
function QueryParam(array $value = [])
{
    return new QueryParamsObject($value);
}

/**
 * High order function to apply $closure to each item of an
 * {@see \Drewlabs\Contracts\Data\EnumerableQueryResult} instance.
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
 * High order function to apply a closure to a collection or list of type
 * {@see \Drewlabs\Contracts\Data\EnumerableQueryResult[]}.
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
 * Creates a `SELECT` type query action using user provided by function user.
 *
 * + SelectQueryAction($id [, array $columns,\Closure $callback])
 * ```php
 * use function Drewlabs\Packages\Database\Proxy\SelectQueryAction;
 *
 * // ...
 *
 * // Example
 * $action = SelectQueryAction($id) // Creates a select by id query
 * ```
 *
 * + SelectQueryAction(array $query [, array $columns,\Closure $callback])
 * + SelectQueryAction(array $query, int $per_page [?int $page = null, array $columns,\Closure $callback])
 * ```php
 * use function Drewlabs\Packages\Database\Proxy\SelectQueryAction;
 *
 * //...
 *
 * // Example
 * $action = SelectQueryAction([
 *  'where' => ['id', 12],
 *  'whereHas' => ['parent', function($q) {
 *      return $q->where('id', <>, 12);
 *  }]
 * ]);
 * ```
 *
 * + SelectQueryAction(FiltersInterface $query [, array $columns,\Closure $callback])
 * + SelectQueryAction(FiltersInterface $query, int $per_page [?int $page = null, array $columns,\Closure $callback])
 * ```php
 * use function Drewlabs\Packages\Database\Proxy\ModelFiltersHandler;
 * use function Drewlabs\Packages\Database\Proxy\SelectQueryAction;
 *
 * // ...
 * // Example
 * $action = SelectQueryAction(ModelFiltersHandler(...));
 * ```
 *
 * @param mixed $payload
 *
 * @return Action
 */
function SelectQueryAction(...$payload)
{
    return Action('SELECT', ...$payload);
}

/**
 * Creates a `UPDATE` type query action using user provided by function user.
 *
 * + UpdateQueryAction($id, array|object $attributes [,\Closure $callback])
 * ```php
 * use function Drewlabs\Packages\Database\Proxy\UpdateQueryAction;
 *
 * // ...
 *
 * // Example
 * $action = UpdateQueryAction($id, ['name' => 'John Doe'])
 * ```
 *
 * + UpdateQueryAction(array $query, array|object $attributes [,\Closure $callback])
 * ```php
 * use function Drewlabs\Packages\Database\Proxy\UpdateQueryAction;
 *
 * // ...
 *
 * // Example
 * $action = UpdateQueryAction(ModelFiltersHandler(...), ['name' => 'John Doe'])
 * ```
 *
 * + UpdateQueryAction(FiltersInterface $query, array|object $attributes [,\Closure $callback])
 * ```php
 * use function Drewlabs\Packages\Database\Proxy\UpdateQueryAction;
 * use function Drewlabs\Packages\Database\Proxy\ModelFiltersHandler;
 *
 * // ...
 *
 * // Example
 * $action = UpdateQueryAction(['where' => ['id' => 3]], ['name' => 'John Doe'])
 * ```
 *
 * @param mixed ...$payload
 *
 * @return Action
 */
function UpdateQueryAction(...$payload)
{
    return Action('UPDATE', ...$payload);
}

/**
 * Creates a `DELETE` type query action using user provided by function user.
 *
 * + DeleteQueryAction($id [,\Closure $callback])
 * ```php
 * use function Drewlabs\Packages\Database\Proxy\DeleteQueryAction;
 *
 * // ...
 *
 * // Example
 * $action = DeleteQueryAction($id)
 * ```
 *
 * + DeleteQueryAction(array $query [,\Closure $callback])
 * ```php
 * use function Drewlabs\Packages\Database\Proxy\DeleteQueryAction;
 *
 * // ...
 *
 * // Example
 * $action = DeleteQueryAction(['where' => ['id' => 3]])
 * ```
 *
 * + DeleteQueryAction(FiltersInterface $query [,\Closure $callback])
 * ```php
 * use function Drewlabs\Packages\Database\Proxy\DeleteQueryAction;
 * use function Drewlabs\Packages\Database\Proxy\ModelFiltersHandler;
 *
 * // ...
 *
 * // Example
 * $action = DeleteQueryAction(ModelFiltersHandler(...))
 * ```
 *
 * @param mixed ...$payload
 *
 * @return Action
 */
function DeleteQueryAction(...$payload)
{
    return Action('DELETE', ...$payload);
}

/**
 * Creates a `CREATE` type query action using user provided by function user.
 *
 * + CreateQueryAction(array $attributes [, array $params, \Closure $callback])
 * ```php
 * use function Drewlabs\Packages\Database\Proxy\CreateQueryAction;
 *
 * // ...
 *
 * // Example
 * $action = CreateQueryAction([...])
 * ```
 *
 * + CreateQueryAction(object $attributes, [, array $params ,\Closure $callback])
 * ```php
 * use function Drewlabs\Packages\Database\Proxy\CreateQueryAction;
 *
 * // ...
 *
 * // Example
 * $object = new stdClass;
 * $object->name = 'John Doe';
 * $object->notes = 67;
 *
 * $action = CreateQueryAction($object);
 * ```
 *
 * @param mixed ...$payload
 *
 * @return Action
 */
function CreateQueryAction(...$payload)
{
    return Action('CREATE', ...$payload);
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
 * @return DMLQueryCommandInterface
 */
function useDMLQueryActionCommand(DMLProvider $instance, ?\Closure $overridesActionHandler = null)
{
    return new class($instance, $overridesActionHandler) {
        /**
         * @var DMLProvider
         */
        private $instance;

        /**
         * @var \Closure|null
         */
        private $overridesActionHandler;

        public function __construct(DMLProvider $instance, ?\Closure $overridesActionHandler = null)
        {
            $this->instance = $instance;
            $this->overridesActionHandler = $overridesActionHandler;
        }

        /**
         * Execute the DML Query action command on developper provided action interface.
         *
         * @return ActionResultInterface
         */
        public function __invoke(ActionsInterface $action, ?\Closure $callback = null)
        {
            // We allow user to provide a custom handler that overrides the
            // default action handler for the command
            if ($this->overridesActionHandler) {
                return ActionResult(($this->overridesActionHandler)($action, $callback));
            }
            $payload = $action->payload();
            $payload = $payload instanceof ActionPayloadInterface ?
                $payload->toArray() : (\is_array($payload) ? $payload : []);
            // Handle switch statements
            switch (strtoupper($action->type())) {
                case 'CREATE':
                case 'DB_CREATE_ACTION':
                    $payload = null !== $callback ? array_merge($payload, [$callback]) : $payload;

                    return ActionResult($this->instance->create(...$payload));
                case 'UPDATE':
                case 'DB_UPDATE_ACTION':
                    $payload = null !== $callback ? array_merge($payload, [$callback]) : $payload;

                    return ActionResult($this->instance->update(...$payload));
                case 'DELETE':
                case 'DB_DELETE_ACTION':
                    return ActionResult($this->instance->delete(...$payload));
                case 'SELECT':
                case 'DB_SELECT_ACTION':
                    $payload = null !== $callback ? array_merge($payload, [$callback]) : $payload;

                    return ActionResult($this->instance->select(...$payload));
                default:
                    throw new InvalidDMLQueryActionException('This '.__CLASS__.' can only handle CREATE,DELETE,UPDATE AND SELECT actions');
            }
        }

        /**
         * Execute the DML Query action command on developper provided action interface.
         *
         * @return ActionResultInterface
         */
        public function exec(ActionsInterface $action, ?\Closure $callback = null)
        {
            return $this->__invoke($action, $callback);
        }
    };
}
