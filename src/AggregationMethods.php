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

namespace Drewlabs\Packages\Database;

class AggregationMethods
{
    /**
     * Method signature for count aggregation on query result.
     */
    public const COUNT = 'count';

    /**
     * Method signature for max aggregation on query result.
     */
    public const MAX = 'max';

    /**
     * Method signature for min aggregation on query result.
     */
    public const MIN = 'min';

    /**
     * Method signature for avg aggregation on query result.
     */
    public const AVERAGE = 'avg';

    /**
     * Method signature for sum aggregation on query result.
     */
    public const SUM = 'sum';
}
