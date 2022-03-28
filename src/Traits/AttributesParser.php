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

use Drewlabs\Contracts\Data\Parser\ModelAttributeParser as ModelAttributesParserContract;

trait AttributesParser
{
    use ContainerAware;

    /**
     * @return array
     */
    private function parseAttributes(array $value)
    {
        return self::createResolver(
            ModelAttributesParserContract::class
        )()->setModel(
            drewlabs_core_create_attribute_getter(
                'model',
                null
            )($this)
        )->setModelInputState($value)
            ->getModelInputState();
    }
}
