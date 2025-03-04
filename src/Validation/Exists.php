<?php

declare(strict_types=1);

namespace Drewlabs\Laravel\Query\Validation;

use Drewlabs\Laravel\Query\Query;
use Illuminate\Contracts\Validation\ValidationRule;
use InvalidArgumentException;

final class Exists implements ValidationRule
{
    use HasBuilder;

    /** @var Query */
    private $builder;

    /** @var string */
    private $property = 'id';

    /**
     * `exists` class constructor
     * 
     * @param Query $builder 
     * @param string $property 
     * @return void 
     */
    public function __construct(string|Query $builder, string $property = 'id')
    {
        $this->property = $property;

        if (\is_string($builder) && class_exists($builder)) {
            $this->builder = Query::new()->fromBuilder($builder);
        } else if (is_string($builder)) {
            $this->builder = Query::new()->from($builder);
        } else {
            $this->builder = $builder;
        }
    }

    /**
     * `exists` rule factory constructor
     * 
     * @param string $url 
     * @param string $property
     * @return static 
     * @throws InvalidArgumentException 
     */
    public static function new(string|Query $builder, string $property = 'id')
    {
        return new static($builder, $property);
    }

    public function validate(string $attribute, $value, \Closure $fail): void
    {
        if ($this->builder->eq($this->property, $value)->count() === 0) {
            $fail(sprintf("%s attribute value is invalid", $attribute));
        }
    }

    public function __invoke(string $attribute, $value, \Closure $fail): void
    {
        $this->validate($attribute, $value, $fail);
    }
}
