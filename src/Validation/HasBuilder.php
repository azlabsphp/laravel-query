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

namespace Drewlabs\Laravel\Query\Validation;

use Drewlabs\Query\Http\Query;

/**
 * @property Query $builder
 */
trait HasBuilder
{
    /**
     * @notImplemented
     *
     * @return static
     */
    public function withAuthorization(string $authToken, string $method = 'Bearer')
    {
        return $this;
    }

    public function where($column, $value = null)
    {
        $this->builder = $this->builder->and($column, null !== $value ? '=' : null, $value);

        return $this;
    }

    public function whereNot($column, $value = null)
    {
        $this->builder = $this->builder->and($column, null !== $value ? '<>' : null, $value);

        return $this;
    }

    public function whereNotNull(string $column)
    {
        $this->builder = $this->builder->notNull($column);

        return $this;
    }

    public function whereNull(string $column)
    {
        $this->builder = $this->builder->isNull($column);

        return $this;
    }
}
