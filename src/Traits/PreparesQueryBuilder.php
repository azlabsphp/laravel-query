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

use Drewlabs\Core\Helpers\Arr;

use function Drewlabs\Packages\Database\Proxy\ModelFiltersHandler;

trait PreparesQueryBuilder
{
    /**
     * Prepare query builder by applying queries to the builder initial state.
     *
     * @param mixed                  $builder
     * @param array|FiltersInterface $query
     *
     * @return mixed
     */
    private function prepareQueryBuilder($builder, $query)
    {
        if (\is_array($query)) {
            return array_reduce(
                Arr::isnotassoclist($query) ? $query : [$query],
                static function ($model, $q) {
                    return ModelFiltersHandler($q)->apply($model);
                },
                $builder
            );
        }

        return $query->apply($builder);
    }
}
