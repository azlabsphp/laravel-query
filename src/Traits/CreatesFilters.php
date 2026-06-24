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

namespace Drewlabs\Laravel\Query\Traits;

use Drewlabs\Query\PreparesFiltersBag;


/** @deprecated v0.4.x implementations provided by the trait makes too much assumption on the class that will use it and heavily rely on metadata programming to resolve model associated with the class, from version 0.5.x code that relies in this trait should provide their own implementation of makeFilters. */
trait CreatesFilters
{
    /**
     * Creates a list of filters based on view model input & query parameters.
     *
     * @param array $defaults Default filters can be passed in by developpers that are merged with
     *                        query filters from builded from view model
     *
     * @return array<string, mixed>
     */
    public function makeFilters(array $defaults = [])
    {
        return PreparesFiltersBag::new($this)->call($this->resolveModel(), $defaults ?? []);
    }

    /**
     * Creates an instance of the attached model.
     *
     * @return mixed
     */
    public function resolveModel()
    {
        if (!method_exists($this, 'getModel')) {
            return null;
        }

        $model = $this->getModel();
        if (is_string($model) && class_exists($model)) {
            return new $model;
        }

        return $model;
    }
}
