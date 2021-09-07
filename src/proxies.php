<?php

namespace Drewlabs\Packages\Database\Proxy;

use Drewlabs\Packages\Database\Helpers\SelectQueryResult;
use Drewlabs\Contracts\Data\DataProviderQueryResultInterface;
use Illuminate\Contracts\Pagination\Paginator;

/**
 * @param Paginator|DataProviderQueryResultInterface $value
 * @return SelectQueryResult 
 */
function SelectQueryResult($value)
{
    return new SelectQueryResult($value);
}