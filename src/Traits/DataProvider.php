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

namespace Drewlabs\Packages\Database\Traits;

use Drewlabs\Contracts\Data\DataProviderHandlerParamsInterface;
use Drewlabs\Core\Data\DataProviderQueryResult;
use Drewlabs\Packages\Database\Extensions\CustomQueryCriteria;

trait DataProvider
{

    /**
     * {@inheritDoc}
     */
    public function create(array $attributes, $params = [])
    {
        $params = $this->parseProviderCreateHandlerParams($params);
        return $this->repository->resetScope()->{$params['method']}(
            $attributes,
            true,
            $params['upsert'],
            $params['upsert_conditions']
        );
    }

    /**
     * {@inheritDoc}
     */
    public function delete($query, $batch = false)
    {
        if (\is_array($query)) {
            return $this->repository->resetScope()->pushFilter(
                (new CustomQueryCriteria)->setQueryFilters(
                    $query
                )
            )->delete([], $batch);
        }

        return $this->repository->resetScope()->deleteById($query);
    }

    /**
     * {@inheritDoc}
     */
    public function get($query = [], $columns = ['*'], $relationQuery = false, $shouldPaginate = false, $limit = null)
    {
        if (!\is_array($query)) {
            return $this->getById($query);
        }
        $relationFn = 'queryRelation';
        if ((!\is_array($relationQuery) && !\is_bool($relationQuery))) {
            $relationQuery = false;
        }
        if (\is_array($relationQuery)) {
            $relationFn = 'loadWith';
        }

        return $shouldPaginate ? $this->repository->resetScope()->pushFilter(
            (new CustomQueryCriteria)
                ->setQueryFilters(null === $query ? [] : $query)
        )->{$relationFn}($relationQuery)->paginate($limit) : new DataProviderQueryResult(
            $this->repository->resetScope()->pushFilter(
                (new CustomQueryCriteria)->setQueryFilters($query)
            )->{$relationFn}($relationQuery)->find([], $columns)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getById($id)
    {
        if (null === $id) {
            return null;
        }
        if (is_numeric($id) || \is_string($id)) {
            return $this->repository->resetScope()->findById($id);
        }
        throw new \RuntimeException('Bad query parameter, valid numeric argument');
    }

    /**
     * {@inheritDoc}
     */
    public function modify($query, array $attributes, $params = [])
    {
        $params = $this->parseProviderUpdateHandlerParams($params);
        if (\is_array($query)) {
            return $this->repository->resetScope()->pushFilter(
                (new CustomQueryCriteria)->setQueryFilters($query)
            )->update($attributes, [], true, $params['should_mass_update']);
        }

        return $this->repository->resetScope()->{$params['method']}(
            $query,
            $attributes,
            true,
            $params['upsert']
        );
    }

    /**
     *
     * @param array|DataProviderHandlerParamsInterface $params
     *
     * @return void
     */
    protected function parseProviderUpdateHandlerParams($params)
    {
        return drewlabs_database_parse_update_handler_params($params);
    }

    /**
     *
     * @param array|DataProviderHandlerParamsInterface $params
     *
     * @return void
     */
    protected function parseProviderCreateHandlerParams($params)
    {
        return drewlabs_database_parse_create_handler_params($params);
    }
}
