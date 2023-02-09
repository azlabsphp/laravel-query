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

/**
 * @deprecated v3.x Not needed for every application models therefore will
 * be removed in future release. Provides an implementation in a data transfert
 * object instead
 */
trait HasLinkAttribute
{
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

    protected function getArrayableAppends()
    {
        $route = $this->getIndexRoute();
        if ($this->withoutAppends) {
            return null !== $route && \is_string($route) ? ['_link'] : [];
        }
        return array_merge(parent::getArrayableAppends(), isset($route) && \is_string($route) ? ['_link'] : []);
    }
}
