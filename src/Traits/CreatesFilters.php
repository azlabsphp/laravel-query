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

use Drewlabs\Query\QueryFiltersBuilder;

trait CreatesFilters
{
    /**
     * Creates a list of filters based on view model input & query
     * parameters.
     *
     * @param array $defaults Default filters can be passed in by developpers that are merged with
     *                        query filters from builded from view model
     *
     * @return array<string, mixed>
     */
    public function makeFilters(array $defaults = [])
    {
        return QueryFiltersBuilder::new($this->resolveModel())->build($this, $defaults ?? []);
    }

    /**
     * Creates an instance of the attached model.
     *
     * @return mixed
     */
    public function resolveModel()
    {
        return \is_string($model = $this->getModel()) ? new $model : $model;
    }
}
