<?php

namespace Drewlabs\Packages\Database;

use Drewlabs\Contracts\Data\DataProviderHandlerParamsInterface;
use Drewlabs\Contracts\Data\DML\DMLProvider;
use Drewlabs\Contracts\Data\Filters\FiltersInterface;
use Drewlabs\Contracts\Data\Model\Model;
use Drewlabs\Contracts\Data\Repository\ModelRepository;
use Drewlabs\Core\Data\DataProviderQueryResult;
use Drewlabs\Packages\Database\Extensions\IlluminateModelRepository;
use Illuminate\Container\Container;
use Illuminate\Support\Traits\ForwardsCalls;

class DMLQueryManager implements DMLProvider
{

    use ForwardsCalls;

    /**
     *
     * @var ModelRepository
     */
    private $repository;

    public function __construct($clazz)
    {
        $this->repository = new IlluminateModelRepository($clazz);
    }

    /**
     * @inheritDoc
     */
    public function createFromAttribute(array $attributes, $params = [])
    {
        $params = $this->parseProviderCreateHandlerParams($params);
        $repository = $this->getModelRepository();
        return $this->forwardCallTo($repository, $params['method'], [
            $attributes,
            true,
            $params['upsert'],
            $params['upsert_conditions']
        ]);
    }

    /**
     * @inheritDoc
     */
    public function create(Model $model, $params = [])
    {
        $params = $this->parseProviderCreateHandlerParams($params);
        $repository = $this->getModelRepository();
        return $this->forwardCallTo($repository, $params['method'], [
            $model->toArray(),
            true,
            true,
            [$model->getPrimaryKey() => $model->getKey()]
        ]);
    }

    /**
     * @inheritDoc
     */
    public function deleteAll(array $query, $hot_operation = false)
    {
        $repository  = $this->getModelRepository();
        if (method_exists($repository, 'pushFilter')) {
            return $this->forwardCallTo(
                $repository,
                'pushFilter',
                [Container::getInstance()->make(FiltersInterface::class)->setQueryFilters($query)]
            )->delete([], $hot_operation);
        } else {
            return $repository->delete($query, $hot_operation);
        }
    }

    /**
     * @inheritDoc
     */
    public function deleteByID($id)
    {
        return (int)($this->getModelRepository()->deleteById($id)) === 0 ? false : true;
    }

    public function delete(Model $model)
    {
        return (int)($this->getModelRepository()->deleteById($model->getKey())) === 0 ? false : true;
    }

    public function selectAll($query = [], $columns = ['*'], $load_relations = false, ?\Closure $dto_transform_fn = null)
    {
        return $dto_transform_fn($this->get($query, $columns, $load_relations, false, null));
    }

    public function select($id, $columns = ['*'], $load_relations = true, ?\Closure $dto_transform_fn = null)
    {
        $repository = $this->getModelRepository();
        if ($load_relations === false && method_exists($repository, 'copyWith')) {
            $repository = $this->forwardCallTo($repository, 'copyWith', [['ignore_relations_on_single_model' => true]]);
        }
        $model = $repository->findById($id, $columns ?? ["*"]);
        return is_null($model) ? $model : (!is_null($dto_transform_fn) && is_callable($dto_transform_fn) ? $dto_transform_fn($model) : $model);
    }

    /**
     * @inheritDoc
     */
    public function updateFromAttributes($query, array $attributes, $params = [])
    {
        $repository  = $this->getModelRepository();
        $params = $this->parseProviderUpdateHandlerParams($params);
        $hot_operation = $params['should_mass_update'];
        return $this->forwardCallTo(
            $repository,
            'pushFilter',
            [Container::getInstance()->make(FiltersInterface::class)->setQueryFilters($query)]
        )->update($attributes, [], true, $hot_operation);
    }

    public function update(Model $model, $attributes = [], $params = [], ?\Closure $dto_transform_fn = null)
    {
        return $this->updateByID(
            $model->getKey(),
            $attributes instanceof Model ? $attributes->toArray() : $attributes,
            $params,
            $dto_transform_fn
        );
    }

    public function updateByID($id, $attributes = [], $params = [], ?\Closure $dto_transform_fn = null)
    {
        $params = $this->parseProviderUpdateHandlerParams($params);
        $this->forwardCallTo($this->getModelRepository(), $params['method'], [$id, $attributes, true, $params['upsert']]);
        return $this->select($id, ["*"], false, $dto_transform_fn);
    }

    /**
     *
     * @return ModelRepository
     */
    public function getModelRepository()
    {
        return $this->repository;
    }

    /**
     * {@inheritDoc}
     */
    public function get($query = [], $columns = array('*'), $relations = false, $paginate = false, $limit = null)
    {
        $relationFn = 'queryRelation';
        $repository = $this->getModelRepository();
        if ((!is_array($relations) && !is_bool($relations))) {
            $relations = false;
        }
        if (is_array($relations)) {
            $relationFn = 'loadWith';
        }
        return $paginate ?
            $this->forwardCallTo(
                $repository,
                'pushFilter',
                [
                    Container::getInstance()
                        ->make(FiltersInterface::class)
                        ->setQueryFilters(is_null($query) ? [] : $query)
                ]
            )
            ->{$relationFn}($relations)
            ->paginate($limit) :
            new DataProviderQueryResult(
                $this->forwardCallTo(
                    $repository,
                    'pushFilter',
                    [Container::getInstance()->make(FiltersInterface::class)->setQueryFilters(is_null($query) ? [] : $query)]
                )
                    ->{$relationFn}($relations)
                    ->find([], $columns)
            );
    }

    /**
     *
     * @param array|DataProviderHandlerParamsInterface $params
     * @return array
     */
    protected function parseProviderUpdateHandlerParams($params)
    {
        $value = $params instanceof DataProviderHandlerParamsInterface ? $params->getParams() : (is_array($params) ? $params : []);
        $value['method'] = !isset($value['method']) ? 'updateById' : (!is_string($value['method']) ? 'updateById' : $value['method']);
        $value['upsert'] = !isset($value['upsert']) ? false : (!is_bool($value['upsert']) ? false : $value['upsert']);
        $value['should_mass_update'] = !isset($value['should_mass_update']) ? false : (!is_bool($value['should_mass_update']) ? false : $value['should_mass_update']);
        return $value;
    }

    /**
     *
     * @param array|DataProviderHandlerParamsInterface $params
     * @return array
     */
    protected function parseProviderCreateHandlerParams($params)
    {
        $value = $params instanceof DataProviderHandlerParamsInterface ? $params->getParams() : (is_array($params) ? $params : []);
        $value['upsert'] = !isset($value['upsert']) ? false : (!is_bool($value['upsert']) ? false : $value['upsert']);
        $value['method'] = !isset($value['method']) ? 'insert' : (!is_string($value['method']) ? 'insert' : $value['method']);
        $value['upsert_conditions'] = !isset($value['upsert_conditions']) ? [] : (!is_array($value['upsert_conditions']) ? [] : $value['upsert_conditions']);
        return $value;
    }
}
