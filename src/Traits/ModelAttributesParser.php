<?php

namespace Drewlabs\Packages\Database\Traits;

use Drewlabs\Contracts\Data\Parser\ModelAttributeParser as ModelAttributesParserContract;
use Illuminate\Container\Container;

trait ModelAttributesParser
{
    /**
     *
     * @param array $value
     * @return array
     */
    private function parseAttributes(array $value)
    {
        return Container::getInstance()->make(ModelAttributesParserContract::class)
            ->setModel(
                drewlabs_core_create_attribute_getter(
                    'model',
                    null
                )($this)
            )->setModelInputState($value)
            ->getModelInputState();
    }
}