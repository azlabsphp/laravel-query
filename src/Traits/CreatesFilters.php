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

use Drewlabs\Packages\Database\QueryFiltersBuilder;

trait CreatesFilters
{


    /**
     * Creates a list of filters based on view model input & query
     * parameters 
     * 
     * @return array<string, mixed>
     */
    public function makeFilters()
    {
        return QueryFiltersBuilder::for($this->resolveModel())->build($this);
    }

    /**
     * Creates an instance of the attached model
     * 
     *
     * @return StorageClient
     */
    public function resolveModel()
    {
        return is_string($model = $this->getModel()) ? self::createResolver($model)() : $model;
    }
}
