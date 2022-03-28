<?php

namespace Drewlabs\Packages\Database\Traits;

use Drewlabs\Packages\Database\FilterQueryParamsParser;

trait EloquentBuilderQueryFilters
{
    /**
     * Dictionnary of model method to query filters.
     *
     * @var array
     */
    private $filters = [];

    /**
     * @var mixed
     */
    private $model;

    public function apply($model)
    {
        $clone = clone $model;
        foreach ($this->filters ?? [] as $key => $value) {
            $method = 'apply' . ucfirst($key);
            if ((null !== $value) && method_exists($this, $method)) {
                $clone = $this->{$method}($clone, $value);
            }
        }
        return $clone;
    }

    public function setQueryFilters(array $list)
    {
        $this->filters = $list;
        return $this;
    }

    /**
     * apply a where query to the model.
     *
     * @param mixed          $model
     * @param array|callable $filter
     *
     * @return mixed
     */
    private function applyWhere($model, $filter)
    {
        if ($filter instanceof \Closure) {
            $model = $model->where($filter);

            return $model;
        }
        $parser = new FilterQueryParamsParser();
        $result = $parser->parse($filter);
        $is_list = array_filter($result, 'is_array') === $result;
        if ($is_list) {
            $model = array_reduce($filter, static function ($model, array $query) {
                if (drewlabs_core_array_is_no_assoc_array_list($query)) {
                    return $model->where($query);
                }
                return $model->where(...$query);
            }, $model);
        } else {
            $model = $model->where(...$filter);
        }
        return $model;
    }

    /**
     * apply a where query to the model.
     *
     * @param mixed $model
     * @param array $filter
     *
     * @return mixed
     */
    private function applyWhereHas($model, $filter)
    {
        $filter = array_filter($filter, 'is_array') === $filter ? $filter : [$filter];
        foreach ($filter as $value) {
            $model = $model->whereHas($value[0], $value[1]);
        }
        return $model;
    }

    /**
     * apply a where query to the model.
     *
     * @param mixed $model
     * @param array $filter
     *
     * @return mixed
     */
    private function applyWhereDoesntHave($model, $filter)
    {
        $filter = array_filter($filter, 'is_array') === $filter ? $filter : [$filter];
        foreach ($filter as $value) {
            $model = $model->whereDoesntHave(...$value);
        }
        return $model;
    }

    /**
     * apply a whereDate query to the model.
     *
     * @param mixed $model
     * @param array $filter
     *
     * @return mixed
     */
    private function applyWhereDate($model, $filter)
    {
        $filter = array_filter($filter, 'is_array') === $filter ? $filter : [$filter];
        foreach ($filter as $value) {
            $model = $model->whereDate(...$value);
        }
        return $model;
    }

    /**
     * Apply a has query.
     *
     * @param mixed $model
     * @param array $filter
     *
     * @return mixed
     */
    private function applyHas($model, $filter)
    {
        if (\is_string($filter)) {
            $model = $model->has($filter);
        }
        if (\is_array($filter)) {
            foreach ($filter as $value) {
                $model = $model->has($value);
            }
        }
        return $model;
    }

    /**
     * Apply a has query.
     *
     * @param mixed $model
     * @param array $filter
     *
     * @return mixed
     */
    private function applyDoesntHave($model, $filter)
    {
        if (\is_string($filter)) {
            $model = $model->doesntHave($filter);
        }
        if (\is_array($filter)) {
            foreach ($filter as $value) {
                $model = $model->doesntHave($value);
            }
        }
        return $model;
    }

    /**
     * apply an orWhere query to the model.
     *
     * @param mixed $model
     * @param array $filter
     *
     * @return mixed
     */
    private function applyOrWhere($model, $filter)
    {
        if ($filter instanceof \Closure) {
            $model = $model->where($filter);
            return $model;
        }
        $parser = new FilterQueryParamsParser();
        $result = $parser->parse($filter);
        $isList = array_filter($result, 'is_array') === $result;
        if ($isList) {
            $model = array_reduce($filter, static function ($model, array $query) {
                if (drewlabs_core_array_is_no_assoc_array_list($query)) {
                    return $model->orWhere($query);
                }
                return $model->orWhere(...$query);
            }, $model);
        } else {
            $model = $model->orWhere(...$filter);
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
    private function applyWhereIn($model, array $filter)
    {
        $filter = array_filter($filter, 'is_array') === $filter ? $filter : [$filter];
        return array_reduce($filter, static function ($carry, $curr) {
            return \count($curr) >= 2 ? $carry->whereIn($curr[0], $curr[1]) : $carry;
        }, $model);
    }

    /**
     * apply a whereBetween query to the model.
     *
     * @param mixed $model
     *
     * @return mixed
     */
    private function applyWhereBetween($model, array $filter)
    {
        return $model->whereBetween($filter[0], $filter[1]);
    }

    /**
     * apply a whereNotIn query to the model.
     *
     * @param mixed $model
     *
     * @return mixed
     */
    private function applyWhereNotIn($model, array $filter)
    {
        $filter = array_filter($filter, 'is_array') === $filter ? $filter : [$filter];
        return array_reduce($filter, static function ($carry, $curr) {
            return \count($curr) >= 2 ? $carry->whereNotIn($curr[0], $curr[1]) : $carry;
        }, $model);
    }

    /**
     * apply an orderBy query to the model.
     *
     * @param mixed $model
     *
     * @return mixed
     */
    private function applyOrderBy($model, array $filter)
    {
        return $model->orderBy($filter['by'], $filter['order']);
    }

    /**
     * Apply group by query on the provided model instance.
     *
     * @param mixed          $model
     * @param array[]|string $filter
     *
     * @return mixed
     */
    private function applyGroupBy($model, $filter)
    {
        return \is_string($filter) ? $model->groupBy($filter) : $model->groupBy(...$filter);
    }

    /**
     * apply a join query on the model query to the model.
     *
     * @param mixed          $model
     * @param array|callable $filter
     *
     * @return mixed
     */
    private function applyJoin($model, $filter)
    {
        return $this->sqlApplyJoinQueries($model, $filter);
    }

    /**
     * apply a right join query on the model.
     *
     * @param mixed          $model
     * @param array|callable $filter
     *
     * @return mixed
     */
    private function applyRightJoin($model, $filter)
    {
        return $this->sqlApplyJoinQueries($model, $filter, 'rightJoin');
    }

    /**
     * apply a left join query on the model.
     *
     * @param mixed          $model
     * @param array|callable $filter
     *
     * @return mixed
     */
    private function applyLeftJoin($model, $filter)
    {
        return $this->sqlApplyJoinQueries($model, $filter, 'leftJoin');
    }

    private function sqlApplyJoinQueries($model, $filter, $method = 'join')
    {
        $result = $this->joinQueryParser->parse($filter);
        $result = array_filter($result, 'is_array') === $result ? $result : [$result];
        foreach ($result as $value) {
            $model = $model->{$method}(...$value);
        }
        return $model;
    }

    /**
     * apply an whereNull query to the model.
     *
     * @param mixed $model
     * @param array $filter
     *
     * @return mixed
     */
    private function applyWhereNull($model, $filter)
    {
        $filter = \is_array($filter) ? $filter : [$filter];
        return array_reduce($filter, static function ($carry, $current) {
            return $carry->whereNull($current);
        }, $model);
    }

    /**
     * apply an whereNotNull query to the model.
     *
     * @param mixed $model
     * @param array $filter
     *
     * @return mixed
     */
    private function applyWhereNotNull($model, $filter)
    {
        $filter = \is_array($filter) ? $filter : [$filter];
        return array_reduce($filter, static function ($carry, $current) {
            return $carry->whereNotNull($current);
        }, $model);
    }

    /**
     * apply an orWhereNull query to the model.
     *
     * @param mixed $model
     * @param array $filter
     *
     * @return mixed
     */
    private function applyOrWhereNull($model, $filter)
    {
        $filter = \is_array($filter) ? $filter : [$filter];
        return array_reduce($filter, static function ($carry, $current) {
            return $carry->orWhereNull($current);
        }, $model);
    }

    /**
     * apply an orWhereNotNull query to the model.
     *
     * @param mixed $model
     * @param array $filter
     *
     * @return mixed
     */
    private function applyOrWhereNotNull($model, $filter)
    {
        $filter = \is_array($filter) ? $filter : [$filter];
        return array_reduce($filter, static function ($carry, $current) {
            return $carry->orWhereNotNull($current);
        }, $model);
    }
}