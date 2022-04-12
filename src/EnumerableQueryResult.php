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

namespace Drewlabs\Packages\Database;

use Drewlabs\Contracts\Data\EnumerableQueryResult as ContractsEnumerableQueryResult;
use function Drewlabs\Support\Proxy\Collection;

use Drewlabs\Support\Traits\MethodProxy;

class EnumerableQueryResult implements ContractsEnumerableQueryResult, \JsonSerializable
{
    use MethodProxy;

    /**
     * List of items that can be manipulated.
     *
     * @var mixed
     */
    private $items_;

    public function __construct($items = null)
    {
        $items = $items ?? [];
        $this->setCollection($items);
    }

    public function __call($name, $arguments)
    {
        if (method_exists($this, $name)) {
            return $this->{$name}(...$arguments);
        }
        if (!\is_object($this->getCollection())) {
            throw new \BadMethodCallException('Method does not exists on class '.__CLASS__);
        }

        return $this->proxy($this->getCollection(), $name, $arguments);
    }

    /**
     * {@inheritDoc}
     */
    public function getCollection()
    {
        return $this->offsetGet('data');
    }

    /**
     * @return array|mixed
     */
    public function items()
    {
        return $this->offsetGet('data');
    }

    /**
     * {@inheritDoc}
     */
    public function setCollection($items)
    {
        if (\is_array($items)) {
            $items = Collection($items);
        }
        $this->offsetSet('data', $items);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset): bool
    {
        return \array_key_exists($offset, $this->items_) && isset($this->items_[$offset]);
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return \array_key_exists($offset, $this->items_) ? $this->items_[$offset] : null;
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value): void
    {
        $this->items_[$offset] = $value;
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset): void
    {
        unset($this->items_[$offset]);
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->items_;
    }
}
