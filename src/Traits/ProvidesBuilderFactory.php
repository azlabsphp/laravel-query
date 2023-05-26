<?php


namespace Drewlabs\Packages\Database\Traits;

use Closure;
use Drewlabs\Query\Contracts\FiltersInterface;

trait ProvidesBuilderFactory
{

    /**
     * Query language builder factory getter.
     *
     * @return Closure(mixed $builder, array|FiltersInterface $query): mixed
     */
    public function builderFactory()
    {
        return $this->builderFactory;
    }

    /**
     * Query Language builder factory setter method.
     *
     * @return self
     */
    public function setBuilderFactory(Closure $factory)
    {
        $this->builderFactory = $factory;
        return $this;
    }
}
