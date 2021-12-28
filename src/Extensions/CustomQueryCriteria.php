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

use Drewlabs\Contracts\Data\ModelFiltersInterface;
use Drewlabs\Contracts\Data\Parser\QueryParser;
use Drewlabs\Packages\Database\FilterQueryParamsParser;
use Drewlabs\Packages\Database\JoinQueryParamsParser;

class CustomQueryCriteria implements ModelFiltersInterface
{
    /**
     * @var string
     */
    public const WHERE = 'where';
    /**
     * @var string
     */
    public const ORWHERE = 'orWhere';
    /**
     * @var string
     */
    public const WHEREIN = 'whereIn';
    /**
     * @var string
     */
    public const WHERENOTIN = 'whereNotIn';
    /**
     * @var string
     */
    public const ORDER_BY = 'orderBy';
    /**
     * @var string
     */
    public const SKIP = 'skip';
    /**
     * @var string
     */
    public const TAKE = 'take';

    /**
     * Dictionnary of model method to query filters.
     *
     * @var array
     */
    protected $query_criteria;

    /**
     * @var mixed
     */
    protected $model;

    /**
     * @var QueryParser
     */
    private $joinQueryParser;

    public function __construct(?array $filter_list = null, ?QueryParser $joinQueryParser = null)
    {
        if (isset($filter_list)) {
            $this->setQueryFilters($filter_list);
        }
        $this->joinQueryParser = null === $joinQueryParser ? new JoinQueryParamsParser() : $joinQueryParser;
    }

    /**
     * {@inheritDoc}
     */
    public function apply($model)
    {
        $_model = clone $model;
        $criteria = $this->query_criteria;
        foreach (array_keys($criteria) as $v) {
            $method = 'apply'.ucfirst($v).'Query';
            if (method_exists($this, $method)) {
                $_model = \call_user_func_array([$this, $method], [$_model, $criteria]);
            }
        }

        return $_model;
    }

    /**
     * {@inheritDoc}
     */
    public function setQueryFilters(array $list)
    {
        $this->query_criteria = $list;

        return $this;
    }

    /**
     * apply a where query to the model.
     *
     * @param mixed          $model
     * @param array|callable $criteria
     *
     * @return mixed
     */
    private function applyWhereQuery($model, $criteria)
    {
        if (\array_key_exists('where', $criteria) && null !== $criteria['where']) {
            if ($criteria['where'] instanceof \Closure) {
                $model = $model->where($criteria['where']);

                return $model;
            }
            $result = (new FilterQueryParamsParser())->parse($criteria['where']);
            $isArrayList = array_filter($result, 'is_array') === $result;
            if ($isArrayList) {
                $model = array_reduce($criteria['where'], static function ($model, array $query) {
                    if (drewlabs_core_array_is_no_assoc_array_list($query)) {
                        return $model->where($query);
                    }

                    return $model->where(...$query);
                }, $model);
            } else {
                $model = $model->where(...$criteria['where']);
            }
        }

        return $model;
    }

    /**
     * apply a where query to the model.
     *
     * @param mixed $model
     * @param array $criteria
     *
     * @return mixed
     */
    private function applyWhereHasQuery($model, $criteria)
    {
        if (\array_key_exists('whereHas', $criteria) && null !== $criteria['whereHas']) {
            $isArrayList = array_filter($criteria['whereHas'], 'is_array') === $criteria['whereHas'];
            if ($isArrayList) {
                foreach ($criteria['whereHas'] as $value) {
                    $model = $model->whereHas($value[0], $value[1]);
                }
            } else {
                $model = $model->whereHas($criteria['whereHas'][0], $criteria['whereHas'][1]);
            }
        }

        return $model;
    }

    /**
     * apply a where query to the model.
     *
     * @param mixed $model
     * @param array $criteria
     *
     * @return mixed
     */
    private function applyWhereDoesntHaveQuery($model, $criteria)
    {
        if (\array_key_exists('whereDoesntHave', $criteria) && null !== $criteria['whereDoesntHave']) {
            $isArrayList = array_filter($criteria['whereDoesntHave'], 'is_array') === $criteria['whereDoesntHave'];
            if ($isArrayList) {
                foreach ($criteria['whereDoesntHave'] as $value) {
                    $model = $model->whereDoesntHave(...$value);
                }
            } else {
                $model = $model->whereDoesntHave(...$criteria['whereDoesntHave']);
            }
        }

        return $model;
    }

    /**
     * apply a whereDate query to the model.
     *
     * @param mixed $model
     * @param array $criteria
     *
     * @return mixed
     */
    private function applyWhereDateQuery($model, $criteria)
    {
        if (\array_key_exists('whereDate', $criteria) && null !== $criteria['whereDate']) {
            $isArrayList = array_filter($criteria['whereDate'], 'is_array') === $criteria['whereDate'];
            if ($isArrayList) {
                foreach ($criteria['whereDate'] as $value) {
                    $model = $model->whereDate(...$value);
                }
            } else {
                $model = $model->whereDate(...$criteria['whereDate']);
            }
        }

        return $model;
    }

    /**
     * Apply a has query.
     *
     * @param mixed $model
     * @param array $criteria
     *
     * @return mixed
     */
    private function applyHasQuery($model, $criteria)
    {
        if (\array_key_exists('has', $criteria) && null !== $criteria['has']) {
            if (\is_string($criteria['has'])) {
                $model = $model->has($criteria['has']);
            }
            if (\is_array($criteria['has'])) {
                foreach ($criteria['has'] as $value) {
                    // code...
                    $model = $model->has($value);
                }
            }
        }

        return $model;
    }

    /**
     * Apply a has query.
     *
     * @param mixed $model
     * @param array $criteria
     *
     * @return mixed
     */
    private function applyDoesntHaveQuery($model, $criteria)
    {
        if (\array_key_exists('doesntHave', $criteria) && null !== $criteria['doesntHave']) {
            if (\is_string($criteria['doesntHave'])) {
                $model = $model->doesntHave($criteria['doesntHave']);
            }
            if (\is_array($criteria['doesntHave'])) {
                foreach ($criteria['doesntHave'] as $value) {
                    $model = $model->doesntHave($value);
                }
            }
        }

        return $model;
    }

    /**
     * apply an orWhere query to the model.
     *
     * @param mixed $model
     * @param array $criteria
     *
     * @return mixed
     */
    private function applyOrWhereQuery($model, $criteria)
    {
        if (\array_key_exists('orWhere', $criteria) && null !== $criteria['orWhere']) {
            if ($criteria['orWhere'] instanceof \Closure) {
                $model = $model->where($criteria['orWhere']);

                return $model;
            }
            $result = (new FilterQueryParamsParser())->parse($criteria['orWhere']);
            $isArrayList = array_filter($result, 'is_array') === $result;
            if ($isArrayList) {
                $model = array_reduce($criteria['orWhere'], static function ($model, array $query) {
                    if (drewlabs_core_array_is_no_assoc_array_list($query)) {
                        return $model->orWhere($query);
                    }

                    return $model->orWhere(...$query);
                }, $model);
            } else {
                $model = $model->orWhere(...$criteria['orWhere']);
            }
        }

        return $model;
    }

    /**
     * apply a whereIn query to the model.
     *
     * @param mixed $model
     *
     * @return mixed
     */
    private function applyWhereInQuery($model, array $criteria)
    {
        if (\array_key_exists('whereIn', $criteria) && null !== $criteria['whereIn']) {
            $isArrayList = array_filter($criteria['whereIn'], 'is_array') === $criteria['whereIn'];
            if ($isArrayList) {
                $model = array_reduce($criteria['whereIn'], static function ($carry, $curr) {
                    return \count($curr) >= 2 ? $carry->whereIn($curr[0], $curr[1]) : $carry;
                }, $model);
            } else {
                $model = (\count($criteria['whereIn']) >= 2) ? $model->whereIn($criteria['whereIn'][0], $criteria['whereIn'][1]) : $model;
            }
        }

        return $model;
    }

    /**
     * apply a whereBetween query to the model.
     *
     * @param mixed $model
     *
     * @return mixed
     */
    private function applyWhereBetweenQuery($model, array $criteria)
    {
        if (\array_key_exists('whereBetween', $criteria) && null !== $criteria['whereBetween']) {
            $model = $model->whereBetween($criteria['whereBetween'][0], $criteria['whereBetween'][1]);
        }

        return $model;
    }

    /**
     * apply a whereNotIn query to the model.
     *
     * @param mixed $model
     *
     * @return mixed
     */
    private function applyWhereNotInQuery($model, array $criteria)
    {
        if (\array_key_exists('whereNotIn', $criteria) && null !== $criteria['whereNotIn']) {
            $isArrayList = array_filter($criteria['whereNotIn'], 'is_array') === $criteria['whereNotIn'];
            if ($isArrayList) {
                $model = array_reduce($criteria['whereNotIn'], static function ($carry, $curr) {
                    return \count($curr) >= 2 ? $carry->whereNotIn($curr[0], $curr[1]) : $carry;
                }, $model);
            } else {
                $model = (\count($criteria['whereNotIn']) >= 2) ? $model->whereNotIn($criteria['whereNotIn'][0], $criteria['whereNotIn'][1]) : $model;
            }
        }

        return $model;
    }

    /**
     * apply an orderBy query to the model.
     *
     * @param mixed $model
     *
     * @return mixed
     */
    private function applyOrderByQuery($model, array $criteria)
    {
        if (\array_key_exists('orderBy', $criteria) && null !== $criteria['orderBy'] && $criteria['orderBy']) {
            $model = $model->orderBy($criteria['orderBy']['by'], $criteria['orderBy']['order']);
        }

        return $model;
    }

    /**
     * Apply group by query on the provided model instance.
     *
     * @param mixed          $model
     * @param array[]|string $criteria
     *
     * @return mixed
     */
    private function applyGroupByQuery($model, array $criteria)
    {
        if (
            \array_key_exists('groupBy', $criteria)
            && null !== $criteria['groupBy'] &&
            (\is_array($criteria['groupBy']) || \is_string($criteria['groupBy']))
        ) {
            if (\is_string($criteria['groupBy'])) {
                $model = $model->groupBy($criteria['groupBy']);
            } else {
                $model = $model->groupBy(...$criteria['groupBy']);
            }
        }

        return $model;
    }

    /**
     * apply a join query on the model query to the model.
     *
     * @param mixed          $model
     * @param array|callable $criteria
     *
     * @return mixed
     */
    private function applyJoinQuery($model, $criteria)
    {
        return $this->sqlApplyJoinQueries($model, $criteria);
    }

    /**
     * apply a right join query on the model.
     *
     * @param mixed          $model
     * @param array|callable $criteria
     *
     * @return mixed
     */
    private function applyRightJoinQuery($model, $criteria)
    {
        return $this->sqlApplyJoinQueries($model, $criteria, 'rightJoin');
    }

    /**
     * apply a left join query on the model.
     *
     * @param mixed          $model
     * @param array|callable $criteria
     *
     * @return mixed
     */
    private function applyLeftJoinQuery($model, $criteria)
    {
        return $this->sqlApplyJoinQueries($model, $criteria, 'leftJoin');
    }

    private function sqlApplyJoinQueries($model, $criteria, $method = 'join')
    {
        if (\array_key_exists($method, $criteria) && null !== $criteria[$method]) {
            $result = $this->joinQueryParser->parse($criteria[$method]);
            $isArrayList = array_filter($result, 'is_array') === $result;
            if ($isArrayList) {
                foreach ($result as $value) {
                    $model = $model->{$method}(...$value);
                }
            } else {
                $model = $model->{$method}(...$result);
            }
        }

        return $model;
    }

    /**
     * apply an skip query to the model.
     *
     * @deprecated 1.0.2
     *
     * @param mixed $model
     *
     * @return mixed
     */
    private function applySkipQuery($model, array $criteria)
    {
        if (\array_key_exists('skip', $criteria) && null !== $criteria['skip']) {
            $model = $model->skip($criteria['skip']);
        }

        return $model;
    }

    /**
     * apply an skip query to the model.
     *
     * @deprecated 1.0.2
     *
     * @param mixed $model
     *
     * @return mixed
     */
    private function applyTakeQuery($model, array $criteria)
    {
        if (\array_key_exists('take', $criteria) && null !== $criteria['take']) {
            $model = $model->take($criteria['take']);
        }

        return $model;
    }

    /**
     * apply an whereNull query to the model.
     *
     * @param mixed $model
     * @param array $criteria
     *
     * @return mixed
     */
    private function applyWhereNullQuery($model, $criteria)
    {
        if (\array_key_exists('whereNull', $criteria) && null !== $criteria['whereNull']) {
            $filters = \is_array($criteria['whereNull']) ? $criteria['whereNull'] : [$criteria['whereNull']];
            $model = array_reduce($filters, static function ($carry, $current) {
                return $carry->whereNull($current);
            }, $model);
        }

        return $model;
    }

    /**
     * apply an whereNotNull query to the model.
     *
     * @param mixed $model
     * @param array $criteria
     *
     * @return mixed
     */
    private function applyWhereNotNullQuery($model, $criteria)
    {
        if (\array_key_exists('whereNotNull', $criteria) && null !== $criteria['whereNotNull']) {
            $filters = \is_array($criteria['whereNotNull']) ? $criteria['whereNotNull'] : [$criteria['whereNotNull']];
            $model = array_reduce($filters, static function ($carry, $current) {
                return $carry->whereNotNull($current);
            }, $model);
        }

        return $model;
    }

    /**
     * apply an orWhereNull query to the model.
     *
     * @param mixed $model
     * @param array $criteria
     *
     * @return mixed
     */
    private function applyOrWhereNullQuery($model, $criteria)
    {
        if (\array_key_exists('orWhereNull', $criteria) && null !== $criteria['orWhereNull']) {
            $filters = \is_array($criteria['orWhereNull']) ? $criteria['orWhereNull'] : [$criteria['orWhereNull']];
            $model = array_reduce($filters, static function ($carry, $current) {
                return $carry->orWhereNull($current);
            }, $model);
        }

        return $model;
    }

    /**
     * apply an orWhereNotNull query to the model.
     *
     * @param mixed $model
     * @param array $criteria
     *
     * @return mixed
     */
    private function applyOrWhereNotNull($model, $criteria)
    {
        if (\array_key_exists('orWhereNotNull', $criteria) && null !== $criteria['orWhereNotNull']) {
            $filters = \is_array($criteria['orWhereNotNull']) ? $criteria['orWhereNotNull'] : [$criteria['orWhereNotNull']];
            $model = array_reduce($filters, static function ($carry, $current) {
                return $carry->orWhereNotNull($current);
            }, $model);
        }

        return $model;
    }
}
