<?php

namespace Drewlabs\Packages\Database;

use Drewlabs\Contracts\Data\Parser\QueryParser;
class JoinQueryParamsParser implements QueryParser
{
    /**
     * {@inheritDoc}
     */
    public function parse(array $params)
    {
        $isArrayList = \array_filter($params, 'is_array') === $params;
        return $isArrayList ? array_values(array_map(function ($item) {
            return $this->parseList($item);
        }, $params)) : $this->parseList($params);
    }

    private function parseList(array $params)
    {
        $allEntiresAreNull = \array_filter($params, function ($item) {
            return is_null($item) || !isset(
                $item);
        }) === $params;
        if ($allEntiresAreNull) {
            // dd($params);
            throw new \InvalidArgumentException('Provided query parameters are not defined');
        }
        // Insure that where not working with associative arrays
        $params = array_values($params);
        // Case the operator part if missing
        if (count($params) === 3) {
            $params[0] = (is_string($params[0]) && !class_exists($params[0])) ? $params[0] : (string)(new QueryParamsObject(
                is_array($params[0]) ? $params[0] : ['model' => $params[0]]
            ));
            $params[1] = is_string($params[1]) ? $params[1] : (string)(new QueryParamsObject($params[1]));
            $params[2] = is_string($params[2]) ? $params[2] : (string)(new QueryParamsObject($params[2]));
            array_splice($params, 2, 1, ['=', $params[2]]);
        } else {
            $params[0] = (is_string($params[0]) && !class_exists($params[0])) ? $params[0] : (new QueryParamsObject(
                is_array($params[0]) ? $params[0] : ['model' => $params[0]]
            ));
            $params[1] = is_string($params[1]) ? $params[1] : (string)(new QueryParamsObject($params[1]));
            $params[3] = is_string($params[3]) ? $params[3] : (string)(new QueryParamsObject($params[3]));
        }
        return $params;
    }
}
