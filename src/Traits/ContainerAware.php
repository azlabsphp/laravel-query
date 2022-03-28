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
use Exception;
use Psr\Container\ContainerInterface;

trait ContainerAware
{
    /**
     * 
     * @param mixed $abstract 
     * @return Closure 
     */
    public static function createResolver($abstract = null)
    {
        /**
         * @return mixed
         */
        return static function ($container = null) use ($abstract) {
            $default = \Illuminate\Container\Container::class;
            if (null === $container && class_exists($default)) {
                $container = forward_static_call([$default, 'getInstance']);
            }
            if (null === $abstract) {
                return $container;
            }
            if ($container instanceof \ArrayAccess) {
                return $container[$abstract];
            }
            if (
                class_exists($default) &&
                $container instanceof \Illuminate\Container\Container
            ) {
                return $container->make($abstract);
            }
            if ($container instanceof ContainerInterface) {
                return $container->get($abstract);
            }
            if (!is_object($container)) {
                throw new Exception('A container instance is required to create a resolver');
            }
            throw new \InvalidArgumentException(
                \get_class($container) .
                    ' is not a ' .
                    ContainerInterface::class .
                    ' nor ' .
                    $default .
                    ' and is not array accessible'
            );
        };
    }
}
