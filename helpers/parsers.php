<?php

use Drewlabs\Contracts\Data\DataProviderHandlerParamsInterface;
use Drewlabs\Packages\Database\EloquentQueryBuilderMethodsEnum;

if (!function_exists('drewlabs_database_parse_update_handler_params')) {
    /**
     *
     * @param array|DataProviderHandlerParamsInterface $params
     * @return array
     */
    function drewlabs_database_parse_update_handler_params($params)
    {
        $value = $params instanceof DataProviderHandlerParamsInterface ? $params->getParams() : (is_array($params) ? $params : []);
        $value['upsert'] = isset($value['upsert']) && boolval($value['upsert']) ? true : false;
        $value['method'] = isset($value['method']) && is_string($value['method']) ? $value['method'] : EloquentQueryBuilderMethodsEnum::UPDATE;
        $value['should_mass_update'] = !isset($value['should_mass_update']) ? false : (!is_bool($value['should_mass_update']) ? false : $value['should_mass_update']);
        return $value;
    }
}

if (!function_exists('drewlabs_database_parse_create_handler_params')) {
    /**
     *
     * @param array|DataProviderHandlerParamsInterface $params
     * @return array
     */
    function drewlabs_database_parse_create_handler_params($params)
    {
        $value = $params instanceof DataProviderHandlerParamsInterface ? $params->getParams() : (is_array($params) ? $params : []);
        $upsert = isset($value['upsert']) && boolval($value['upsert']) ? true : false;
        $method = isset($value['method']) && is_string($value['method']) ? $value['method'] : ($upsert ? EloquentQueryBuilderMethodsEnum::UPSERT : EloquentQueryBuilderMethodsEnum::CREATE);
        $upsert_conditions = $upsert ? (isset($value['upsert_conditions']) && is_array($value['upsert_conditions']) ? $value['upsert_conditions'] : []) : [];
        return array_merge($value, [
            'method' => $method,
            'upsert_conditions' => $upsert_conditions,
            'upsert' => $upsert
        ]);
    }
}


if (!function_exists('drewlabs_database_validate_dynamic_callback')) {

    /**
     * Generate a list of relations method from the provided in the dynamic callback
     *
     * @param string $callback
     * @return array
     */
    function drewlabs_database_parse_dynamic_callback(string $callback, string $method = 'insert')
    {
        $cbs = explode('__', \drewlabs_core_strings_contains($callback, '::') ? explode('::', $callback)[1] : $callback);
        if (!array_slice($cbs, 0, 1) === $method) {
            // Throw a new Exception
            throw new \RuntimeException();
        }
        return $cbs;
    }
}