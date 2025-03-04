<?php

declare(strict_types=1);

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
        $this->builder = $this->builder->and($column, !is_null($value) ? '=' : null, $value);
        return $this;
    }

    public function whereNot($column, $value = null)
    {
        $this->builder = $this->builder->and($column, !is_null($value) ? '<>' : null, $value);
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
