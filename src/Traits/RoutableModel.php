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

use Illuminate\Support\Facades\URL;

trait RoutableModel
{
    /**
     * [[link]] attribute getter.
     *
     * @return string
     */
    public function getLinkAttribute()
    {
        $id = $this->ressourceId();
        $idParam = $this->getKey();
        $route = $this->getIndexRoute();
        if (null === $id || null === $idParam || null === $route) {
            return null;
        }

        return URL::route(
            $route,
            array_merge(
                [$id => $idParam],
                $this->routeTemplateParams &&
                    \is_array($this->routeTemplateParams) ?
                    $this->routeTemplateParams : []
            )
        );
    }

    /**
     * Returns the value matching the id parameter to be passed to the ressource identifier.
     *
     * @deprecated 1.0.72
     *
     * @return int|string
     */
    protected function getRouteIdParam()
    {
        return $this->getKey();
    }

    /**
     * Returns the ressource identifier parameter name for the given model.
     *
     * @return string|null
     */
    protected function ressourceId()
    {
        return $this->ressourceIdParam ?? 'id';
    }

    /**
     * Returns the name of the index route for the given model.
     *
     * @return string|null
     */
    protected function getIndexRoute()
    {
        return $this->indexRoute ?? null;
    }
}
