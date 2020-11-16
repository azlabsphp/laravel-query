<?php

namespace Drewlabs\Packages\Database\Traits;

use Illuminate\Http\Request;

/**
 * @deprecated v1.1.2-dev-master
 */

trait IlluminateBaseModel
{

    /**
     * Get the query parameters that exists on the class fillable property and trie building model filter query
     * @param Request $request
     * @return array
     */
    public function parseRequestQueryFilters(Request $request)
    {
        $filters = [];
        if ($request->has($this->getPrimaryKey()) && !\is_null($request->get($this->getPrimaryKey()))) {
            $filters['where'][] = array($this->getPrimaryKey(), $request->get($this->getPrimaryKey()));
        }
        foreach ($request->all() as $key => $value) {
            # code...
            $searchable = array_merge($this->getFillables(), $this->guarded);
            if (!empty($value)) {
                if (in_array($key, $searchable)) {
                    $filters['orWhere'][] = array($key, 'like', '%' . $value . '%');
                } else if (\drewlabs_core_strings_contains($key, ['__'])) {
                    $exploded = \explode('__', $key);
                    $relation = $exploded[0];
                    $column = $exploded[1];
                    if (method_exists($this, $relation) && !is_null($column)) {
                        $filters['whereHas'][] = array($relation, function ($query) use ($value, $column) {
                            if (is_array($value)) {
                                $query->whereIn($column, $value);
                                return;
                            }
                            $operator = is_numeric($value) || is_bool($value) ? '=' : 'like';
                            $value = is_numeric($value) ? $value : '%' . $value . '%';
                            $query->where($column, $operator, $value);
                        });
                    }
                }
            }
        }
        return $filters;
    }

    public function filtersFromHttpRequestQuery(Request $request)
    {
        $filters = $this->parseRequestQueryFilters($request);
        if ($request->has('_query') && \drewlabs_core_array_is_arrayable($request->get('_query')) && \drewlabs_core_array_is_assoc($request->get('_query'))) {
            $query = $request->get('_query');
            foreach ($query as $key => $value) {
                # code...
                $parsed_value = \drewlabs_database_parse_query_method_params($key, $value);
                if (empty($parsed_value)) {
                    continue;
                }
                if (!\drewlabs_core_array_is_arrayable($parsed_value)) {
                    $filters[$key] = $parsed_value;
                    continue;
                }
                $filters[$key] = array_merge(isset($filters[$key]) ? $filters[$key] : [], $parsed_value);
            }
        }
        return $filters;
    }
}
