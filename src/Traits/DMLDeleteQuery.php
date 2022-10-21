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

namespace Drewlabs\Packages\Database\Traits;

use Drewlabs\Contracts\Data\Filters\FiltersInterface;
use Drewlabs\Packages\Database\EloquentQueryBuilderMethods;

trait DMLDeleteQuery
{
    use PreparesQueryBuilder;

    public function delete(...$args)
    {
        return $this->model->getConnection()->transaction(function () use ($args) {
            return $this->overload($args, [
                'deleteV1',
                'deleteV2',
                'deleteV3',
                'deleteV4',
            ]);
        });
    }

    /**
     * @return bool
     */
    private function deleteV1(int $id)
    {
        return $this->deleteV2((string) $id);
    }

    /**
     * @return bool
     */
    private function deleteV2(string $id)
    {
        return 1 === (int) ($this->deleteV3([
            'where' => [
                drewlabs_core_create_attribute_getter('model', null)($this)->getPrimaryKey(),
                $id,
            ],
        ])) ? true : false;
    }

    /**
     * @return int
     */
    private function deleteV3(array $query, ?bool $batch = false)
    {
        return $this->deleteCommand($query, $batch);
    }

    /**
     * @return int
     */
    private function deleteV4(FiltersInterface $query, ?bool $batch = false)
    {
        return $this->deleteCommand($query, $batch);
    }

    /**
     * Executes the delete query on the query model
     * 
     * @param FiltersInterface|array $query 
     * @param bool $batch 
     * @return mixed 
     */
    private function deleteCommand($query, bool $batch = false)
    {
        return $batch ? $this->proxy(
            $this->prepareQueryBuilder(drewlabs_core_create_attribute_getter('model', null)($this), $query),
            EloquentQueryBuilderMethods::DELETE,
            []
        ) : array_reduce($this->select($query)->all(), function ($carry, $value) {
            $this->proxy(
                $value,
                EloquentQueryBuilderMethods::DELETE,
                []
            );
            ++$carry;

            return $carry;
        }, 0);
    }
}
