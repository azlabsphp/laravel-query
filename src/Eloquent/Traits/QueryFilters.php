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

namespace Drewlabs\Packages\Database\Eloquent\Traits;

use Drewlabs\Core\Helpers\Arr;
use Drewlabs\Packages\Database\Query\ConditionQuery;
use Illuminate\Contracts\Database\Query\Builder;

trait QueryFilters
{
    /**
     * Query filters dictionary.
     *
     * @var array
     */
    private $filters = [];

    /**
     * Query builder instance.
     *
     * @var object
     */
    private $model;

    public function apply($builder)
    {
        $object = clone $builder;
        foreach ($this->filters ?? [] as $key => $value) {
            if ((null !== $value) && method_exists($this, $key)) {
                $object = $this->{$key}($object, $value);
            }
        }

        return $object;
    }

    public function setQueryFilters(array $list)
    {
        $this->filters = $list;

        return $this;
    }

    private function where($builder, $filter)
    {
        if ($filter instanceof \Closure) {
            return $builder->where($filter);
        }

        return Arr::isList($result = (new ConditionQuery())->parse($filter)) ? array_reduce($result, static function ($builder, array $query) {
            return \is_array($query) ? $builder->where(...array_values($query)) : $builder->where($query);
        }, $builder) : $builder->where(...$result);
    }

    private function whereHas($builder, $filter)
    {
        $filter = array_filter($filter, 'is_array') === $filter ? $filter : [$filter];
        foreach ($filter as $value) {
            // To avoid query to throw, we check if the count of parameter isn't less than 2
            // Case it's less than 2 we skip to the next iteration
            if (!\is_array($value) || \count($value) < 2) {
                continue;
            }
            $builder = $builder->whereHas(...array_values($value));
        }

        return $builder;
    }

    private function whereDoesntHave($builder, $filter)
    {
        $filter = array_filter($filter, 'is_array') === $filter ? $filter : [$filter];
        foreach ($filter as $value) {
            // To avoid query to throw, we check if the count of parameter isn't less than 2
            // Case it's less than 2 we skip to the next iteration
            if (!\is_array($value) || \count($value) < 2) {
                continue;
            }
            $builder = $builder->whereDoesntHave(...$value);
        }

        return $builder;
    }

    private function whereDate($builder, $filter)
    {
        $filter = array_filter($filter, 'is_array') === $filter ? $filter : [$filter];
        foreach ($filter as $value) {
            if (!\is_array($value)) {
                continue;
            }
            $builder = $builder->whereDate(...$value);
        }

        return $builder;
    }

    private function orWhereDate($builder, $filter)
    {
        $filter = array_filter($filter, 'is_array') === $filter ? $filter : [$filter];
        foreach ($filter as $value) {
            if (!\is_array($value)) {
                continue;
            }
            $builder = $builder->whereDate(...$value);
        }

        return $builder;
    }

    private function has($builder, $filter)
    {
        if (\is_string($filter)) {
            return $builder->has($filter);
        }
        $operators = ['>=', '<=', '<', '>', '<>', '!='];
        if (\is_array($filter) && (false !== Arr::search($filter[1] ?? null, $operators))) {
            return $builder->has(...$filter);
        }
        if (\is_array($filter)) {
            foreach ($filter as $value) {
                $builder = $builder->has($value);
            }
        }

        return $builder;
    }

    private function doesntHave($builder, $filter)
    {
        $filter = \is_array($filter) ? $filter : [$filter];
        foreach ($filter as $value) {
            $value = \is_array($value) ? $value : [$value];
            $builder = $builder->doesntHave(...array_values($value));
        }

        return $builder;
    }

    private function orWhere($builder, $filter)
    {
        if ($filter instanceof \Closure) {
            return $builder->where($filter);
        }
        
        return Arr::isList($result = (new ConditionQuery())->parse($filter)) ? array_reduce($result, static function ($builder, array $query) {
            // In case the internal query is not an array, we simply pass it to the illuminate query builder
            // Which may throws if the parameters are not supported
            return \is_array($query) ? $builder->orWhere(...array_values($query)) : $builder->orWhere($query);
        }, $builder) : $builder->orWhere(...$result);
    }

    private function whereIn($builder, array $filter)
    {
        return array_reduce(array_filter($filter, 'is_array') === $filter ? $filter : [$filter], static function ($carry, $curr) {
            // To make sure the builder does not throw we ignore any in query providing invalid
            // arguments
            return \count($curr) >= 2 ? $carry->whereIn($curr[0], $curr[1]) : $carry;
        }, $builder);
    }

    private function whereBetween($builder, array $filter)
    {
        if (\count($filter) < 2) {
            return $builder;
        }
        return $builder->whereBetween(...array_values($filter));
    }

    private function whereNotIn($builder, array $filter)
    {
        return array_reduce(array_filter($filter, 'is_array') === $filter ? $filter : [$filter], static function ($carry, $curr) {
            // To make sure the builder does not throw we ignore any in query providing invalid
            // arguments
            return \count($curr) >= 2 ? $carry->whereNotIn($curr[0], $curr[1]) : $carry;
        }, $builder);
    }

    private function orderBy($builder, array $filters)
    {
        $validate = static function ($values) {
            if (empty($values)) {
                return false;
            }
            foreach ($values as $value) {
                if (!isset($value['order']) || !isset($value['by'])) {
                    return false;
                }
            }
            return true;
        };
        // Case the filters is a data structure or type [['order' => '...', 'by' => '...']]
        // we apply the filters
        if ($validate($filters = Arr::isassoc($filters) ? [$filters] : $filters)) {
            return array_reduce($filters, static function ($builder, $current) {
                return $builder->orderBy($current['by'], $current['order']);
            }, $builder);
        }
        // Else we simply returns the builder without throwing any exception
        // As we consider it a falsy query parameter
        return $builder;
    }

    private function groupBy($builder, $filter)
    {
        return \is_string($filter) ? $builder->groupBy($filter) : $builder->groupBy(...$filter);
    }

    private function join($builder, $filter)
    {
        return $this->sqlApplyJoinQueries($builder, $filter);
    }

    private function rightJoin($builder, $filter)
    {
        return $this->sqlApplyJoinQueries($builder, $filter, 'rightJoin');
    }

    private function leftJoin($builder, $filter)
    {
        return $this->sqlApplyJoinQueries($builder, $filter, 'leftJoin');
    }

    private function sqlApplyJoinQueries($builder, $filter, $method = 'join')
    {
        $result = $this->joinQuery->parse($filter);
        $result = Arr::isList($result) ? $result : [$result];
        foreach ($result as $value) {
            $builder = $builder->{$method}(...$value);
        }
        return $builder;
    }

    private function whereNull($builder, $filter)
    {
        $filter = \is_array($filter) ? $filter : [$filter];
        return array_reduce($filter, static function ($carry, $current) {
            return $carry->whereNull($current);
        }, $builder);
    }

    private function whereNotNull($builder, $filter)
    {
        $filter = \is_array($filter) ? $filter : [$filter];
        return array_reduce($filter, static function ($carry, $current) {
            return $carry->whereNotNull($current);
        }, $builder);
    }

    private function orWhereNull($builder, $filter)
    {
        $filter = \is_array($filter) ? $filter : [$filter];
        return array_reduce($filter, static function ($carry, $current) {
            return $carry->orWhereNull($current);
        }, $builder);
    }

    /**
     * Apply `whereNotNull` query on the builder instance
     * 
     * @param Builder $builder 
     * @param mixed $filter
     * 
     * @return Builder 
     */
    private function orWhereNotNull($builder, $filter)
    {
        $filter = \is_array($filter) ? $filter : [$filter];
        return array_reduce($filter, static function ($carry, $current) {
            return $carry->orWhereNotNull($current);
        }, $builder);
    }

    /**
     * Invoke `limit` query on the query builder
     * 
     * @param Builder $builder
     * 
     * @param int $limit
     * 
     * @return Builder 
     */
    public function limit($builder, int $limit)
    {
        return $builder->limit($limit);
    }
}
