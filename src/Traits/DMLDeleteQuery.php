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

use Drewlabs\Packages\Database\EloquentQueryBuilderMethodsEnum;
use Drewlabs\Packages\Database\Extensions\CustomQueryCriteria;
use Illuminate\Support\Enumerable;

trait DMLDeleteQuery
{
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
    public function deleteV1(int $id)
    {
        return $this->deleteV2((string) $id);
    }

    /**
     * @return bool
     */
    public function deleteV2(string $id)
    {
        return 1 === (int) ($this->deleteV3(
            [
                'where' => [
                    drewlabs_core_create_attribute_getter('model', null)($this)->getPrimaryKey(),
                    $id,
                ],
            ]
        )) ? true : false;
    }

    /**
     * @return int
     */
    public function deleteV3(array $query)
    {
        return $this->applyDelete($query);
    }

    /**
     * @return int
     */
    public function deleteV4(array $query, bool $batch)
    {
        return $this->applyDelete($query, $batch);
    }

    private function applyDelete(array $query, bool $batch = false)
    {
        if ($batch) {
            return $this->forwardCallTo(
                array_reduce(
                    drewlabs_core_array_is_no_assoc_array_list($query) ?
                        $query :
                        [$query],
                    static function ($model, $q) {
                        return (new CustomQueryCriteria($q))->apply($model);
                    },
                    drewlabs_core_create_attribute_getter('model', null)($this)
                ),
                EloquentQueryBuilderMethodsEnum::DELETE,
                []
            );
        } else {
            // Loop through the matching columns and update each
            return array_reduce(
                $this->select($query)->all(),
                function ($carry, $value) {
                    $this->forwardCallTo(
                        $value,
                        EloquentQueryBuilderMethodsEnum::DELETE,
                        []
                    );
                    ++$carry;

                    return $carry;
                },
                0
            );
        }
    }
}
