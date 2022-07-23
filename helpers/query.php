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

use Drewlabs\Contracts\Data\Model\GuardedModel;
use Drewlabs\Contracts\Data\Model\Model;
use Drewlabs\Contracts\Data\Model\Parseable;
use Drewlabs\Packages\Database\QueryFiltersBuilder;


if (!function_exists('drewlabs_databse_parse_client_request_query')) {
    /**
     * Parse query provided in a client /GET request.
     *
     * @param Model|GuardedModel|Parseable $model
     * @param mixed                        $params_bag
     *
     * @throws \InvalidArgumentException
     *
     * @return array|mixed
     */
    function drewlabs_databse_parse_client_request_query($model, $params_bag)
    {
        return QueryFiltersBuilder::for($model)->build($params_bag);
    }
}
