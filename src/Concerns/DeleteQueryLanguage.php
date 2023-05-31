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

use Drewlabs\Query\Contracts\FiltersInterface;
use Drewlabs\Query\Contracts\Queryable;
use Drewlabs\Query\Contracts\TransactionManagerInterface;

/**
 * @mixin \Drewlabs\Laravel\Query\Contracts\ProvidesFiltersFactory
 *
 * @property TransactionManagerInterface transactions
 * @property Queryable                   queryable
 */
trait DeleteQueryLanguage
{
    public function delete(...$args)
    {
        return $this->transactions->transaction(function () use ($args) {
            return $this->overload($args, [
                function (int $id) {
                    return 1 === (int) $this->deleteCommand(['and' => [$this->queryable->getPrimaryKey(), $id]], false);
                },
                function (string $id) {
                    return 1 === (int) $this->deleteCommand(['and' => [$this->queryable->getPrimaryKey(), $id]], false);
                },
                function (array $query, ?bool $batch = false) {
                    return $this->deleteCommand($query, $batch);
                },
                function (FiltersInterface $query, ?bool $batch = false) {
                    return $this->deleteCommand($query, $batch);
                },
            ]);
        });
    }

    private function deleteCommand($query, bool $batch = false)
    {
        return $batch ? $this->builderFactory()($this->queryable, $query)->delete() : array_reduce($this->select($query)->all(), static function ($carry, $instance) {
            $instance->delete();
            ++$carry;

            return $carry;
        }, 0);
    }
}
