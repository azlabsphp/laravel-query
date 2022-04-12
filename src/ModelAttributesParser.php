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

use Drewlabs\Contracts\Data\Model\Model;
use Drewlabs\Contracts\Data\Model\Parseable;
use Drewlabs\Contracts\Data\Parser\ModelAttributeParser;
use Drewlabs\Packages\Database\Exceptions\ModelTypeException;

class ModelAttributesParser implements ModelAttributeParser
{
    /**
     * @var Model|Parseable
     */
    protected $model;

    /**
     * Dictionnary of key value pairs of the data to be inserted.
     *
     * @var array
     */
    protected $attributes;

    public function __destruct()
    {
        unset($this->hasher, $this->model, $this->columns_map);
    }

    /**
     * {@inheritDoc}
     */
    public function setModel($model)
    {
        $this->model = clone $model;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getModel()
    {
        return clone $this->model;
    }

    /**
     * {@inheritDoc}
     */
    public function setModelInputState(array $inputs)
    {
        if ((!$this->model instanceof Parseable) &&
            !(method_exists($this->model, 'getFillable'))
        ) {
            throw new ModelTypeException([Model::class, Parseable::class], ' or must at least contains mthods getFillable()');
        }
        $this->attributes = $this->buildAttributes($inputs);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getModelInputState()
    {
        return $this->attributes;
    }

    /**
     * Build current model fillable columns insertion key value pair based on matching field in the request input.
     *
     * @return array
     */
    private function buildAttributes(array $inputs)
    {
        // Get the value of the model fillable property
        $fillable = $this->model->getFillable() ?? [];

        // We assume that if developper do not provide fillable properties
        // the input from request should be passed to
        if (empty($fillable)) {
            return $inputs;
        }
        $attributes = [];
        foreach ($fillable as $value) {
            if (\array_key_exists($value, $inputs)) {
                $attributes[$value] = $inputs[$value];
            }
        }

        return $attributes;
    }
}
