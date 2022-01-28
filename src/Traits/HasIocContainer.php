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

use Closure;
use Illuminate\Container\Container;
use Psr\Container\ContainerInterface;

trait HasIocContainer
{
    /**
     * 
     * @param mixed $abstract 
     * @return Closure 
     */
    public static function createResolver($abstract = null)
    {
        /**
         * @return ContainerInterface|Container|mixed
         */
        return static function ($container = null) use ($abstract) {
            if (null === $container) {
                $container = forward_static_call([Container::class, 'getInstance']);
            }
            if (null === $abstract) {
                return $container;
            }
            if ($container instanceof \ArrayAccess) {
                return $container[$abstract];
            }
            if (class_exists(Container::class) && ($container instanceof Container)) {
                return $container->make($abstract);
            }
            if ($container instanceof ContainerInterface) {
                return $container->get($abstract);
            }
            throw new \InvalidArgumentException(\get_class($container) . ' is not a ' . ContainerInterface::class . ' nor ' . Container::class . ' and is not array accessible');
        };
    }
}
