<?php

namespace Drewlabs\Packages\Database;

use Drewlabs\Packages\Database\Contracts\IQueryParser;

class FilterQueryParamsParser implements IQueryParser
{
    /**
     * {@inheritDoc}
     */
    public function parse(array $params)
    {
        $isArrayList = \array_filter($params, 'is_array') === $params;
        return $isArrayList ? array_values(array_map(function ($item) {
            return $this->parseListElement($item);
        }, $params)) : $this->parseListElement($params);
    }

    private function parseListElement(array $params)
    {
        $allEntiresAreNull = \array_filter($params, function ($item) {
            return is_null($item) || !isset($item);
        }) === $params;
        if ($allEntiresAreNull) {
            // dd($params);
            throw new \InvalidArgumentException('Provided query parameters are not defined');
        }
        // Insure that where not working with associative arrays
        $params = array_values($params);
        // If the first value of the array is an array, parse it else return it
        $params[0] = is_array($params[0]) ? (string)(new QueryParamsObject($params[0])) : $params[0];
        return $params;
    }
}
