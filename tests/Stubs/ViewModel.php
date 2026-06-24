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

namespace Drewlabs\Laravel\Query\Tests\Stubs;


/**
 * @property array $inputs
 */
trait ViewModel
{
    /** @param string $key */
    public function get($key)
    {
        return $this->inputs[$key] ?? null;
    }

    /** @param string $key */
    public function has($key)
    {
        return isset($this->inputs[$key]);
    }

    public function all()
    {
        return $this->inputs;
    }
}
