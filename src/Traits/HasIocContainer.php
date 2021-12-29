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

use Illuminate\Container\Container;
use Psr\Container\ContainerInterface;

trait HasIocContainer
{
    public function createResolver($abstract)
    {
        return static function ($container = null) use ($abstract) {
            if (null === $container) {
                $container = forward_static_call([Container::class, 'getInstance']);
            }
            if ($container instanceof ContainerInterface) {
                return $container->get($abstract);
            }
            if ($container instanceof \ArrayAccess) {
                return $container[$abstract];
            }
            if ($container instanceof Container) {
                return $container->make($abstract);
            }
            throw new \InvalidArgumentException(\get_class($container).' is not a '.ContainerInterface::class.' nor '.Container::class.' and is not array accessible');
        };
    }
}
