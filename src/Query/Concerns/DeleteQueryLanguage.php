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

namespace Drewlabs\Packages\Database\Query\Concerns;

use Drewlabs\Contracts\Data\Filters\FiltersInterface;
use Drewlabs\Packages\Database\Contracts\TransactionManagerInterface;
use Drewlabs\Packages\Database\Eloquent\QueryMethod;

/**
 * @property TransactionManagerInterface transactions
 */
trait DeleteQueryLanguage
{
    public function delete(...$args)
    {
        return $this->transactions->transaction(function () use ($args) {
            return $this->overload($args, [
                function (int $id) {
                    return 1 === (int) $this->deleteCommand(['where' => [drewlabs_core_create_attribute_getter('model', null)($this)->getPrimaryKey(), $id]], false);
                },
                function (string $id) {
                    return 1 === (int) $this->deleteCommand(['where' => [drewlabs_core_create_attribute_getter('model', null)($this)->getPrimaryKey(), $id]], false);
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
        return $batch ? $this->proxy(
            $this->builderFactory()(drewlabs_core_create_attribute_getter('model', null)($this), $query),
            QueryMethod::DELETE,
            []
        ) : array_reduce($this->select($query)->all(), function ($carry, $value) {
            $this->proxy($value, QueryMethod::DELETE, []);
            ++$carry;

            return $carry;
        }, 0);
    }
}
