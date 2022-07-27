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

use Drewlabs\Contracts\Data\Parser\ModelAttributeParser;
use Drewlabs\Packages\Database\Exceptions\ModelTypeException;

final class ModelAttributesParser implements ModelAttributeParser
{
    /**
     * @var \Drewlabs\Contracts\Data\Model\Parseable|mixed
     */
    private $model;

    /**
     * Dictionnary of key value pairs of the data to be inserted.
     *
     * @var array
     */
    private $attributes;

    /**
     * Creates an attribute builder instance from the model parameter
     * 
     * @param string|object $model 
     * @return static 
     */
    public static function new($model)
    {
        $static =  new static;
        $static->model = is_string($model) ? new $model : $model;
        return $static;
    }

    /**
     * Build Model attributest from a dirty attributes provided by the library user
     * 
     * @param array $dirty 
     * @return array 
     */
    public function build(array $dirty)
    {
        if (!(method_exists($this->model, 'getFillable'))) {
            throw new ModelTypeException([Model::class, Parseable::class], ' or must at least contains mthods getFillable()');
        }
        return $this->buildAttributes($dirty);
    }

    public function __destruct()
    {
        unset($this->model);
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

    //#region Deprecation
    /**
     * {@inheritDoc}
     * 
     * @deprecated v2.4.x
     */
    public function setModel($model)
    {
        $this->model = clone $model;

        return $this;
    }

    /**
     * {@inheritDoc}
     * 
     * @deprecated v2.4.x
     */
    public function getModel()
    {
        return clone $this->model;
    }

    /**
     * {@inheritDoc}
     * 
     * @deprecated v2.4.x
     */
    public function setModelInputState(array $inputs)
    {
        $this->attributes = $this->build($inputs);
        return $this;
    }

    /**
     * {@inheritDoc}
     * 
     * @deprecated v2.4.x
     */
    public function getModelInputState()
    {
        return $this->attributes;
    }
    //#endregion Deprecation
}
