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

namespace Drewlabs\Packages\Database\Extensions;

use Drewlabs\Contracts\Data\Model\Model;
use Drewlabs\Contracts\Data\Parser\ModelAttributeParser;
use Drewlabs\Contracts\Data\Repository\ModelRepository as ModelRepositoryInterface;
use Drewlabs\Contracts\Data\Repository\ParseableRepository;
use Drewlabs\Packages\Database\Contracts\TransactionUtils;
use Drewlabs\Packages\Database\DynamicCRUDQueryHandler;
use Drewlabs\Packages\Database\Traits\ModelRepository;
use Drewlabs\Support\Immutable\ValueObject;
use Exception;
use Psr\Container\ContainerInterface;

/**
 * @deprecated v2.0.x Deprecated in favor of {@see EloquentDMLManager} implementation
 * 
 * @method mixed                                       insert(array $values, bool $parse_inputs = false, $upsert = false, $conditions = array())
 * @method mixed                                       insert(array $values, array $conditions)
 * @method mixed                                       insert(array $values)
 * @method mixed                                       insertMany(array $values, bool $parse_inputs)
 * @method mixed                                       insertMany(array $values)
 * @method Drewlabs\Contracts\Data\Model\Model|mixed   find(string $id, array $columns = ['*'])
 * @method Drewlabs\Contracts\Data\Model\Model|mixed   find(int $id, array $columns = ['*'])
 * @method Drewlabs\Contracts\Data\Model\Model[]|mixed find(array $conditions = [], array $columns = ['*'])
 * @method int                                         update(string $id, $attributes = [])
 * @method int                                         update(int $id, array $attributes = [])
 * @method int                                         update(array $values)
 * @method int                                         update(array $values, bool $hot_operation = false)
 * @method int                                         update(array $values, array $conditions = [])
 * @method int                                         update(array $values, array $conditions = [], bool $hot_operation = false)
 * @method int|mixed                                   delete(string $id)
 * @method int|mixed                                   delete(int $id)
 * @method int|mixed                                   delete()
 * @method int|mixed                                   delete(bool $hot_operation = false)
 * @method int|mixed                                   delete(array $conditions = [])
 * @method int|mixed                                   delete(array $conditions, bool $hot_operation = false)
 */
final class IlluminateModelRepository extends ValueObject implements ParseableRepository, ModelRepositoryInterface
{
    use ModelRepository;

    /**
     * Create an instance of the model repository class.
     *
     * @param string $modelClass
     */
    public function __construct(
        $modelClass = null,
        ?ContainerInterface $container = null,
        ?TransactionUtils $transaction = null,
        ?ModelAttributeParser $modelAttributesParser = null
    ) {
        // Call the parent constructor to initialize the class
        $attributes = [
            'container' => $container,
            'transactionUtils' => $transaction ?? self::createResolver(TransactionUtils::class)($container),
            'attribute_parser' => $modelAttributesParser ?? self::createResolver(ModelAttributeParser::class)($container),
        ];
        $model_class = $modelClass;
        if (null !== $model_class) {
            $model = $this->internalMakeModel($model_class, $container);
            if (!(\is_string($model_class)) || !($model instanceof Model)) {
                throw new Exception('Constructor parameter must be an instance of string, must be a valid class that exists, and the class must be an instance of '.Model::class);
            }
            $attributes = array_merge($attributes, [
                'model_instance' => $model,
                'model_class' => $model_class,
            ]);
        }
        parent::__construct($attributes);
    }

    /**
     * Handle dynamic method calls on the model repository instance.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (\is_string($method) && drewlabs_core_strings_contains($method, '__')) {
            $method = drewlabs_core_strings_contains($method, '::') ? explode('::', $method)[1] : $method;
            $items = explode('__', $method);
            // To be used to call the insert or update method on the model
            if ('insert' === $items[0]) {
                return (new DynamicCRUDQueryHandler())->bindTransactionHandler(
                    drewlabs_core_create_attribute_getter('transactionUtils', null)($this)
                )
                    ->bindRepository($this)
                    ->create(\array_slice($items, 1), ...$parameters);
            } elseif ('update' === $items[0]) {
                return (new DynamicCRUDQueryHandler())->bindTransactionHandler(
                    drewlabs_core_create_attribute_getter('transactionUtils', null)($this)
                )
                    ->bindRepository($this)
                    ->update(\array_slice($items, 1), ...$parameters);
            }
            throw new Exception('Error . Undefined method '.$method.' on the model repository class');
        }
    }

    public function setModel($modelClass)
    {
        $that = drewlabs_core_copy_object($this)->resolveRepositoryModel($modelClass);

        return $that;
    }

    /**
     * {@inheritDoc}
     */
    public function makeModel()
    {
        return $this->internalMakeModel($this->getModel(), drewlabs_core_create_attribute_getter('container', null)($this));
    }

    /**
     * {@inheritDoc}
     */
    public function getModel()
    {
        return drewlabs_core_create_attribute_getter('model_class', null)($this);
    }

    /**
     * {@inheritDoc}
     */
    public function modelPrimaryKey()
    {
        return $this->makeModel()->getPrimaryKey();
    }

    /**
     * {@inheritDoc}
     */
    public function modelAttributesParser()
    {
        return drewlabs_core_create_attribute_getter(
            'attribute_parser',
            null
        )($this) ?? self::createResolver(ModelAttributeParser::class)();
    }

    /**
     * {@inheritDoc}
     */
    public function bindAttributesParser(ModelAttributeParser $parser)
    {
        return drewlabs_core_create_attribute_setter('attribute_parser', $parser)($this);
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
            'filters',
            'ignore_relations_on_single_model',
        ];
    }

    /**
     * @return self
     */
    private function resolveModel($clazz = null, ?ContainerInterface $container = null)
    {
        $model_class = $clazz ?? $this->getModel();
        $model = $this->internalMakeModel($model_class, $container);
        if (!(\is_string($model_class)) || !($model instanceof Model)) {
            throw new Exception('Constructor parameter must be an instance of string, must be a valid class that exists, and the class must be an instance of '.Model::class);
        }
        $self = $this->copyWith([
            'model_instance' => $model,
            'model_class' => $model_class,
        ]);

        return $self;
    }

    private function internalMakeModel(string $clazz, ?ContainerInterface $container = null)
    {
        return self::createResolver($clazz)($container);
    }
}
