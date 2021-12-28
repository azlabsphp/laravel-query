<?php

namespace Drewlabs\Packages\Database\Traits;

use Illuminate\Support\Facades\URL;

trait RoutableModel
{

    /**
     * Returns the value matching the id parameter to be passed to the ressource identifier
     * @deprecated 1.0.72
     * @return int|string
     */
    protected function getRouteIdParam()
    {
        return $this->getKey();
    }

    /**
     * [[link]] attribute getter
     *
     * @return string
     */
    public function getLinkAttribute()
    {
        $id = $this->ressourceIdentifier();
        $idParam = $this->getKey();
        $route =  $this->getIndexRoute();
        if (is_null($id) || is_null($idParam) || is_null($route)) {
            return null;
        }
        return URL::route($route, array_merge(
            array($id => $idParam),
            $this->routeTemplateParams &&
                is_array($this->routeTemplateParams) ?
                $this->routeTemplateParams : []
        ));
    }

    /**
     * Returns the ressource identifier parameter name for the given model
     *
     * @return string|null
     */
    protected function ressourceIdentifier()
    {
        return $this->ressourceIdParam ?? 'id';
    }

    /**
     * Returns the name of the index route for the given model
     *
     * @return string|null
     */
    protected function getIndexRoute()
    {
        return $this->indexRoute ?? null;
    }
}
