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

use Drewlabs\Laravel\Query\Query;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;

final class Exists implements ValidationRule
{
    use HasBuilder;

    /** @var Query */
    private $builder;

    /** @var string */
    private $property = 'id';

    /**
     * `exists` class constructor.
     *
     * @param Query $builder
     *
     * @return void
     */
    public function __construct(string|Query $builder, string $property = 'id')
    {
        $this->property = $property;

        if (\is_string($builder) && class_exists($builder) && is_subclass_of($builder, Model::class)) {
            $this->builder = Query::new()->fromBuilder(forward_static_call([$builder, 'query']));
        } elseif (\is_string($builder)) {
            $this->builder = Query::new()->from($builder);
        } else {
            $this->builder = $builder;
        }
    }

    public function __invoke(string $attribute, $value, \Closure $fail): void
    {
        $this->validate($attribute, $value, $fail);
    }

    /**
     * `exists` rule factory constructor.
     *
     * @throws \InvalidArgumentException
     *
     * @return static
     */
    public static function new(string|Query $builder, string $property = 'id')
    {
        return new static($builder, $property);
    }

    public function validate(string $attribute, $value, \Closure $fail): void
    {
        if (0 === $this->builder->eq($this->property, $value)->count()) {
            $fail(sprintf('%s attribute value is invalid', $attribute));
        }
    }
}
