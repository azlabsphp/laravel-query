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

namespace Drewlabs\Laravel\Query\Concerns;

use Drewlabs\Core\Helpers\Str;

use function Drewlabs\Laravel\Query\Proxy\DMLManager;

use Drewlabs\Laravel\Query\QueryableRelations;
use Drewlabs\Query\Contracts\Queryable;
use Drewlabs\Query\Contracts\TransactionManagerInterface;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin \Drewlabs\Laravel\Query\Contracts\ProvidesFiltersFactory
 *
 * @property TransactionManagerInterface transactions
 * @property Queryable|Model             queryable
 */
trait CreateQueryLanguage
{
    public function create(...$args)
    {
        return $this->transactions->transaction(function () use ($args) {
            return $this->overload($args, [
                function ($attributes, \Closure $callback = null) {
                    $callback = $callback ?: static function ($param) {
                        return $param;
                    };

                    return $callback($this->queryable->create($this->parseAttributes($this->attributesToArray($attributes))));
                },
                function ($attributes, array $params, \Closure $callback = null) {
                    return $this->executeCreateQuery($attributes, $params ?? [], false, $callback);
                },
                function ($attributes, array $params, bool $batch, \Closure $callback = null) {
                    return $this->executeCreateQuery($attributes, $params, $batch, $callback);
                },
            ]);
        });
    }

    /**
     * Base method that provides create implementation of the DML manager class.
     *
     * @param array|object $attributes
     *
     * @throws \RuntimeException
     *
     * @return mixed
     */
    private function executeCreateQuery($attributes, array $params, bool $batch = false, \Closure $callback = null)
    {
        $callback = $callback ?: static function ($param) {
            return $param;
        };
        $attributes = $this->attributesToArray($attributes);
        $upsertConditions = $params['upsert_conditions'] ?? [];
        $keys = array_merge($params['relations'] ?? []);
        if (!empty($keys)) {
            // Creates a copy of the relation in order to maintain the state of the keys unchanged
            // accross changes that happen during execution
            $relations = [...$keys];
            // TODO: If the current model contains parent relations, create the parent relation
            $attributes = $this->createParentIfExists($this->queryable, $attributes, $relations);
            // To avoid key index issues, we reset the relations array keys if any unset() call
            // was made on the relations variable
            $instance = !empty($upsertConditions) ? $this->queryable->updateOrCreate($upsertConditions, $this->parseAttributes($attributes)) : $this->queryable->create($this->parseAttributes($attributes));
            // For the touched model, we create the attached relations provided by the library user
            if (!empty($relations = array_values($relations))) {
                QueryableRelations::new($instance)->create($relations, $attributes, $batch);
            }

            return $callback($this->select($instance->getKey(), ['*', ...$keys]));
        }

        return $callback(!empty($upsertConditions) ? $this->queryable->updateOrCreate($upsertConditions, $this->parseAttributes($attributes)) : $this->queryable->create($this->parseAttributes($attributes)));
    }

    /**
     * Implements a clause that insert model parent attributes and set the foreign
     * key back on the model attributes.
     *
     * It acts like a before create model event listener
     *
     * @param mixed $instance
     *
     * @return array
     */
    private function createParentIfExists($instance, array $attributes, array &$relations)
    {
        for ($i = 0; $i < \count($relations); ++$i) {
            $value = $relations[$i];
            // If attributes does not contains entry with the current relation name
            // there is not need to process further therefore we simply continues
            if (!isset($attributes[$value]) || empty($attributes[$value])) {
                continue;
            }
            $result = $instance->$value();
            // We escapes relations that are not belongs to relations
            if (!($result instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo)) {
                continue;
            }
            $parent = $result->getRelated();
            $foreignKey = $result->getForeignKeyName();
            // We continue the loop if the Belongs to method call does not return
            // a valid belongs to class for the related model
            if ((null === $parent) || (null === $foreignKey)) {
                continue;
            }
            $embededRelations = [];
            foreach ($relations as $rel) {
                // code...
                if (Str::startsWith($rel, "$value.")) {
                    $embededRelations[] = Str::after("$value.", $rel);
                    unset($relations[$rel]);
                }
            }
            $createdInstance = DMLManager($parent)->create($attributes[$value], [
                'relations' => $embededRelations,
            ]);
            // Once the create query of the parent model is executed, we add the foreign
            // key to the current instance attributes
            $attributes[$foreignKey] = $createdInstance->getKey();
            // Remove the relation from the list of relations to create after model
            // gets created
            unset($relations[$i], $attributes[$value]);
        }

        return $attributes;
    }
}
