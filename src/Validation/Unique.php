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
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique as Fluent;

/**
 * @method static when($value, $callback, $default = null)
 * @method static unless($value, $callback, $default = null)
 * @method static where($column, $value = null)
 * @method static whereNot($column, $value)
 * @method static whereNull($column)
 * @method static whereNotNull($column)
 * @method static whereIn($column, array $values)
 * @method static whereNotIn($column, array $values)
 * @method static using(\Closure $project)
 * @method static ignore($id, string $idColumn = null)
 */
final class Unique
{
    /** @var Fluent */
    private $decorated;

    /** @var string */
    private $table;

    /**
     * creates a new rule instance.
     */
    public function __construct(string $table, ?string $column = null)
    {
        if (!empty($table)) {
            $this->table = $table;
            $this->decorated = Rule::unique($table, $column);
        }
    }

    public function __call($name, $arguments)
    {
        return $this->proxy($this->decorated, $name, $arguments);
    }

    /**
     * converts the rule to a validation string.
     */
    public function __toString(): string
    {
        return $this->decorated->__toString();
    }

    /**
     * @param string|Fluent $table
     * @param string|null   $column
     *
     * @return static
     */
    public static function new($table, $column = null): self
    {
        if ($table instanceof Fluent) {
            $instance = new static('');

            return $instance->decorate($table);
        }

        return new static($table, $column);
    }

    /**
     * Provides an implementation arround illuminate `Unique::ignore(...)` method that
     * performs a query to select the value matching the id value to ignore in order to
     * avoit SQL injection.
     *
     * @param mixed       $id
     * @param string|null $column
     *
     * @throws \InvalidArgumentException
     *
     * @return $this
     */
    public function ignoreSafe($id, string $column = 'id')
    {
        if (empty($id)) {
            return $this;
        }

        $column = $column ?? 'id';
        $value = \is_string($this->table) && class_exists($this->table) ? Query::new()->fromBuilder(forward_static_call([$this->table, 'query']))->and($column, $id)->first() : Query::new()->from($this->table)->and($column, $id)->first();

        if ($value) {
            $this->decorated = $this->decorated->ignore($value->{$column}, $column);
        }

        return $this;
    }

    /**
     * set the decorated fluent unique rule.
     *
     * @return static
     */
    private function decorate(Fluent $o): self
    {
        $this->decorated = $o;

        return $this;
    }

    private function proxy($object, $method, $args = [], ?\Closure $default = null)
    {
        try {
            // Call the method on the provided object
            return $object->{$method}(...$args);
        } catch (\Error|\BadMethodCallException $e) {
            // Call the default method if the specified method does not exits
            if ((null !== $default) && \is_callable($default)) {
                return $default(...$args);
            }
            $pattern = '~^Call to undefined method (?P<class>[^:]+)::(?P<method>[^\(]+)\(\)$~';
            if (!preg_match($pattern, $e->getMessage(), $matches)) {
                throw $e;
            }
            if ($matches['class'] !== $object::class || $matches['method'] !== $method) {
                throw $e;
            }
            throw new \BadMethodCallException(sprintf('Call to undefined method %s::%s()', static::class, $method));
        }
    }
}
