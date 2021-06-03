<?php

namespace Drewlabs\Packages\Database\Extensions;

use Drewlabs\Contracts\Data\Model\Model;
use Drewlabs\Contracts\Data\Parser\ModelAttributeParser;
use Drewlabs\Contracts\Data\Repository\ParseableRepository;
use Drewlabs\Contracts\EntityObject\AbstractEntityObject;
use Drewlabs\Core\Data\Exceptions\RepositoryException;
use Drewlabs\Core\Data\Traits\ModelRepository;
use Drewlabs\Packages\Database\DynamicCRUDQueryHandler;
use Psr\Container\ContainerInterface;
use Drewlabs\Packages\Database\Contracts\TransactionUtils;
use Drewlabs\Contracts\Data\Repository\ModelRepository as ModelRepositoryInterface;

/**
 * @package Drewlabs\Packages\Database\Extensions
 */
final class IlluminateModelRepository extends AbstractEntityObject implements ParseableRepository, ModelRepositoryInterface
{
    use ModelRepository;

    /**
     * Create an instance of the model repository class
     *
     * @param string $modelClass
     */
    public function __construct(
        $modelClass = null,
        ContainerInterface $container = null,
        TransactionUtils $transaction = null,
        ModelAttributeParser $modelAttributesParser = null
    ) {
        $illuminateContainerClazz = "Illuminate\\Container\\Container";
        $container = $container ?? (class_exists($illuminateContainerClazz) ? forward_static_call([$illuminateContainerClazz, 'getInstance']) : null);
        if (is_null($container)) {
            throw new \RuntimeException(\sprintf('Repository class required an instance of %s, Please install the illuminate/container package or, make sure the framework has a class that implements the psr4 container contract that is passed as dependency to the class constructor', ContainerInterface::class));
        }
        // Call the parent constructor to initialize the class
        parent::__construct([
            'container' => $container,
            'transactionUtils' => $transaction ?? $container->get(\Drewlabs\Packages\Database\Contracts\TransactionUtils::class),
            'attribute_parser' => $modelAttributesParser ?? $container->get(ModelAttributeParser::class)
        ]);
        if (isset($modelClass)) {
            $this->resolveModel($modelClass, $this->container);
        }
    }

    protected function getJsonableAttributes()
    {
        return [
            'container',
            'model_instance',
            'attribute_parser',
            'model_class',
            'transactionUtils',
            'query_model_relation',
            'skip_filters',
            'filters'
        ];
    }

    public function deserializeModelClassAttribute($value)
    {

    }

    public function setModel($modelClass)
    {
        $that = drewlabs_core_copy_object($this)->resolveRepositoryModel($modelClass);
        return $that;
    }

    /**
     * @return static
     */
    private function resolveModel($clazz = null, ContainerInterface $container = null)
    {
        $model_class = $clazz ?? $this->getModel();
        $container = $container ?? \drewlabs_core_create_attribute_getter('container', null)($this);
        $model = $this->internalMakeModel($model_class, $container);
        if (!(is_string($model_class)) || !($model instanceof Model)) {
            throw new RepositoryException("Constructor parameter must be an instance of string, must be a valid class that exists, and the class must be an instance of " . Model::class);
        }
        $this->model_instance = $model;
        $this->model_class = $model_class;
        return $this;
    }

    private function internalMakeModel(string $clazz, ContainerInterface $container = null)
    {
        $container = $container ?? \drewlabs_core_create_attribute_getter('container', null)($this);
        return $container ? $container->get($clazz) : new $clazz;
    }

    /**
     * @inheritDoc
     */
    public function makeModel()
    {
        return $this->internalMakeModel($this->getModel(), \drewlabs_core_create_attribute_getter('container', null)($this));
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
        return \drewlabs_core_create_attribute_getter('attribute_parser', null)($this) ?? (\drewlabs_core_create_attribute_getter('container', null)($this))->get(ModelAttributeParser::class);
    }

    /**
     * @inheritDoc
     */
    public function bindAttributesParser(ModelAttributeParser $parser)
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
                return (new DynamicCRUDQueryHandler())->bindTransactionHandler(
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
