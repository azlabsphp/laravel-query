<?php

namespace Drewlabs\Packages\Database\Extensions;

use Drewlabs\Contracts\Data\DataRepository\Services\IModelAttributesParser;
use Drewlabs\Contracts\Data\IModelable;
use Drewlabs\Contracts\Data\ModelInterface;
use Drewlabs\Contracts\Data\ParseableModelRepository;
use Drewlabs\Contracts\EntityObject\AbstractEntityObject;
use Drewlabs\Core\Data\Exceptions\RepositoryException;
use Drewlabs\Core\Data\Traits\ModelRepository;
use Drewlabs\Packages\Database\DynamicCRUDQueryHandler;
use Illuminate\Container\Container;

/**
 * @package Drewlabs\Packages\Database\Extensions
 */
final class IlluminateModelRepository extends AbstractEntityObject implements ParseableModelRepository
{
    use ModelRepository;
    /**
     * Create an instance of the model repository class
     *
     * @param string $modelClass
     */
    public function __construct($modelClass = null)
    {
        parent::__construct([]);
        if (isset($modelClass)) {
            $this->setModel($modelClass);
        }
        \drewlabs_core_create_attribute_setter('transactionUtils', Container::getInstance()->make(\Drewlabs\Packages\Database\Contracts\TransactionUtils::class))($this);
    }

    protected function getJsonableAttributes()
    {
        return [
            'attribute_parser',
            'model_class',
            'transactionUtils',
            'query_model_relation',
            'skip_filters',
            'filters'

        ];
    }

    public function setModel($modelClass)
    {
        $that = \drewlabs_core_create_attribute_setter('model_class', $modelClass)($this);
        $that->validateModelClass();
        // Create the model instance from the passed configuration
        $that = \drewlabs_core_create_attribute_setter('model', $this->makeModel())($that);
        return $that;
    }

    private function validateModelClass()
    {
        $model_class = $this->getModel();
        if (!(is_string($model_class)) || !($this->makeModel() instanceof ModelInterface)) {
            throw new RepositoryException("Constructor parameter must be an instance of string, must be a valid class that exists, and the class must be an instance of " . IModelable::class);
        }
    }

    /**
     * @inheritDoc
     */
    public function makeModel()
    {
        return Container::getInstance()->make($this->getModel());
    }

    /**
     * @inheritDoc
     */
    public function getModel()
    {
        return \drewlabs_core_create_attribute_getter('model_class', null)($this);
    }

    /**
     * @inheritDoc
     */
    public function modelPrimaryKey()
    {
        return $this->makeModel()->getPrimaryKey();
    }

    /**
     * @inheritDoc
     */
    public function modelAttributesParser()
    {
        return \drewlabs_core_create_attribute_getter('attribute_parser', null)($this) ?? Container::getInstance()->make(IModelAttributesParser::class);
    }

    /**
     * @inheritDoc
     */
    public function bindAttributesParser(IModelAttributesParser $parser)
    {
        return \drewlabs_core_create_attribute_setter('attribute_parser', $parser)($this);
    }

    /**
     * Handle dynamic method calls on the model repository instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (is_string($method) && \drewlabs_core_strings_contains($method, '__')) {
            $method = \drewlabs_core_strings_contains($method, '::') ? explode('::', $method)[1] : $method;
            $items = explode('__', $method);
            // To be used to call the insert or update method on the model
            if ($items[0] === 'insert') {
                return (new DynamicCRUDQueryHandler())->bindTransactionHandler(
                    \drewlabs_core_create_attribute_getter('transactionUtils', null)($this)
                )
                    ->bindRepository($this)
                    ->create(array_slice($items, 1), ...$parameters);
            } else if ($items[0] === 'update') {
                (new DynamicCRUDQueryHandler())->bindTransactionHandler(
                    \drewlabs_core_create_attribute_getter('transactionUtils', null)($this)
                )
                    ->bindRepository($this)
                    ->update(array_slice($items, 1), ...$parameters);
            } else {
                throw new RepositoryException("Error . Undefined method " . $method . " on the model repository class");
            }
        }
    }
}
