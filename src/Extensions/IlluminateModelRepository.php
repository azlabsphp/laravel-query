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
     *
     * @var static
     */
    private static $instance;

    /**
     * Create an instance of the model repository class
     *
     * @param string $modelClass
     */
    public function __construct($modelClass = null)
    {
        parent::__construct([]);
        static::setInstance($this);
        if (isset($modelClass)) {
            $self = static::getInstance()->setModel($modelClass);
        }
        $self = \drewlabs_core_create_attribute_setter('transactionUtils', Container::getInstance()->make(\Drewlabs\Packages\Database\Contracts\TransactionUtils::class))($self);
        static::$instance = $self;
    }

    protected function getJsonableAttributes()
    {
        return [
            'model_instance',
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
        $that = \drewlabs_core_create_attribute_setter('model_class', $modelClass)(static::getInstance());
        $that->validateModelClass();
        // Create the model instance from the passed configuration
        $that = \drewlabs_core_create_attribute_setter('model_instance', $that->makeModel())($that);
        return $that;
    }

    private function validateModelClass()
    {
        $model_class = static::getInstance()->getModel();
        if (!(is_string($model_class)) || !(static::getInstance()->makeModel() instanceof ModelInterface)) {
            throw new RepositoryException("Constructor parameter must be an instance of string, must be a valid class that exists, and the class must be an instance of " . IModelable::class);
        }
    }

    /**
     * @inheritDoc
     */
    public function makeModel()
    {
        return Container::getInstance()->make(static::getInstance()->getModel());
    }

    /**
     * @inheritDoc
     */
    public function getModel()
    {
        return \drewlabs_core_create_attribute_getter('model_class', null)(static::getInstance());
    }

    /**
     * @inheritDoc
     */
    public function modelPrimaryKey()
    {
        return static::getInstance()->makeModel()->getPrimaryKey();
    }

    /**
     * @inheritDoc
     */
    public function modelAttributesParser()
    {
        return \drewlabs_core_create_attribute_getter('attribute_parser', null)(static::getInstance()) ?? Container::getInstance()->make(IModelAttributesParser::class);
    }

    /**
     * @inheritDoc
     */
    public function bindAttributesParser(IModelAttributesParser $parser)
    {
        return \drewlabs_core_create_attribute_setter('attribute_parser', $parser)(static::getInstance());
    }

    protected static function getInstance()
    {
        return static::$instance;
    }

    /**
     *
     * @param static $instance
     * @return void
     */
    private static function setInstance($instance)
    {
        static::$instance = $instance;
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
                    \drewlabs_core_create_attribute_getter('transactionUtils', null)(static::getInstance())
                )
                    ->bindRepository(static::getInstance())
                    ->create(array_slice($items, 1), ...$parameters);
            } else if ($items[0] === 'update') {
                (new DynamicCRUDQueryHandler())->bindTransactionHandler(
                    \drewlabs_core_create_attribute_getter('transactionUtils', null)(static::getInstance())
                )
                    ->bindRepository(static::getInstance())
                    ->update(array_slice($items, 1), ...$parameters);
            } else {
                throw new RepositoryException("Error . Undefined method " . $method . " on the model repository class");
            }
        }
    }
}
