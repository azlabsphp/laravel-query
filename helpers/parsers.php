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

use Drewlabs\Contracts\Data\DataProviderHandlerParamsInterface;
use Drewlabs\Packages\Database\Eloquent\QueryMethod;

if (!function_exists('drewlabs_database_parse_update_handler_params')) {
    /**
     * @param array|DataProviderHandlerParamsInterface $params
     *
     * @deprecated v2.3.x
     *
     * @return array
     */
    function drewlabs_database_parse_update_handler_params($params)
    {
        $value = $params instanceof DataProviderHandlerParamsInterface ? $params->getParams() : (is_array($params) ? $params : []);
        $value['upsert'] = (bool) ($value['upsert'] ?? true);
        $value['method'] = isset($value['method']) && is_string($value['method']) ? $value['method'] : QueryMethod::UPDATE;

        return $value;
    }
}

if (!function_exists('drewlabs_database_parse_create_handler_params')) {
    /**
     * @param array|DataProviderHandlerParamsInterface $params
     *
     * @deprecated v2.3.x
     *
     * @return array
     */
    function drewlabs_database_parse_create_handler_params($params)
    {
        $value = $params instanceof DataProviderHandlerParamsInterface ? $params->getParams() : (is_array($params) ? $params : []);
        $upsert_conditions = isset($value['upsert_conditions']) && is_array($value['upsert_conditions']) ? $value['upsert_conditions'] : [];
        $upsert = !empty($upsert_conditions) ? true : false;
        $method = isset($value['method']) && is_string($value['method']) ? $value['method'] : ($upsert ? QueryMethod::UPSERT : QueryMethod::CREATE);

        return array_merge($value, [
            'method' => $method,
            'upsert_conditions' => $upsert_conditions,
            'upsert' => $upsert,
        ]);
    }
}

if (!function_exists('drewlabs_database_validate_dynamic_callback')) {

    /**
     * Generate a list of relations method from the provided in the dynamic callback.
     *
     * @deprecated v2.3.x
     *
     * @return array
     */
    function drewlabs_database_parse_dynamic_callback(string $callback, string $method = 'insert')
    {
        $cbs = explode('__', drewlabs_core_strings_contains($callback, '::') ? explode('::', $callback)[1] : $callback);
        if (!array_slice($cbs, 0, 1) === $method) {
            // Throw a new Exception
            throw new \RuntimeException();
        }

        return $cbs;
    }
}
