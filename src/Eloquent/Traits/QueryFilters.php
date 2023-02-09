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

trait QueryFilters
{
    /**
     * Query filters dictionary
     *
     * @var array
     */
    private $filters = [];

    /**
     * Query builder instance
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
            $builder = $builder->where($filter);

            return $builder;
        }
        $parser = new ConditionQuery();
        if (Arr::isList($result = $parser->parse($filter))) {
            $builder = array_reduce($result, static function ($builder, array $query) {
                return $builder->where(...array_values($query));
            }, $builder);
        } else {
            $builder = $builder->where(...$result);
        }

        return $builder;
    }

    private function whereHas($builder, $filter)
    {
        $filter = array_filter($filter, 'is_array') === $filter ? $filter : [$filter];
        foreach ($filter as $value) {
            $builder = $builder->whereHas($value[0], $value[1]);
        }

        return $builder;
    }

    private function whereDoesntHave($builder, $filter)
    {
        $filter = array_filter($filter, 'is_array') === $filter ? $filter : [$filter];
        foreach ($filter as $value) {
            $builder = $builder->whereDoesntHave(...$value);
        }

        return $builder;
    }

    private function whereDate($builder, $filter)
    {
        $filter = array_filter($filter, 'is_array') === $filter ? $filter : [$filter];
        foreach ($filter as $value) {
            $builder = $builder->whereDate(...$value);
        }

        return $builder;
    }

    private function orWhereDate($builder, $filter)
    {
        $filter = array_filter($filter, 'is_array') === $filter ? $filter : [$filter];
        foreach ($filter as $value) {
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
        if (is_array($filter)) {
            foreach ($filter as $value) {
                $builder = $builder->has($value);
            }
        }
        return $builder;
    }

    private function doesntHave($builder, $filter)
    {
        if (\is_string($filter)) {
            $builder = $builder->doesntHave($filter);
        }
        if (\is_array($filter)) {
            foreach ($filter as $value) {
                $builder = $builder->doesntHave($value);
            }
        }

        return $builder;
    }

    private function orWhere($builder, $filter)
    {
        if ($filter instanceof \Closure) {
            $builder = $builder->where($filter);

            return $builder;
        }
        $parser = new ConditionQuery();
        if (Arr::isList($result = $parser->parse($filter))) {
            $builder = array_reduce($result, static function ($builder, array $query) {
                return $builder->orWhere(...array_values($query));
            }, $builder);
        } else {
            $builder = $builder->orWhere(...$result);
        }

        return $builder;
    }

    private function whereIn($builder, array $filter)
    {
        $filter = array_filter($filter, 'is_array') === $filter ? $filter : [$filter];

        return array_reduce($filter, static function ($carry, $curr) {
            return \count($curr) >= 2 ? $carry->whereIn($curr[0], $curr[1]) : $carry;
        }, $builder);
    }

    private function whereBetween($builder, array $filter)
    {
        return $builder->whereBetween($filter[0], $filter[1]);
    }

    private function whereNotIn($builder, array $filter)
    {
        $filter = array_filter($filter, 'is_array') === $filter ? $filter : [$filter];

        return array_reduce($filter, static function ($carry, $curr) {
            return \count($curr) >= 2 ? $carry->whereNotIn($curr[0], $curr[1]) : $carry;
        }, $builder);
    }

    private function orderBy($builder, array $filters)
    {
        // TODO: In future release, valide the filters inputs
        if (!Arr::isassoc($filters)) {
            return array_reduce($filters, static function ($builder, $current) {
                return $builder->orderBy($current['by'], $current['order']);
            }, $builder);
        }

        return $builder->orderBy($filters['by'], $filters['order']);
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

    private function orWhereNotNull($builder, $filter)
    {
        $filter = \is_array($filter) ? $filter : [$filter];

        return array_reduce($filter, static function ($carry, $current) {
            return $carry->orWhereNotNull($current);
        }, $builder);
    }
}
