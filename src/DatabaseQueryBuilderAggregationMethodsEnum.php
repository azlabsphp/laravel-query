<?php

namespace Drewlabs\Packages\Database;

class DatabaseQueryBuilderAggregationMethodsEnum
{

    /**
     * Method signature for count aggregation on query result
     */
    public const COUNT = 'count';

    /**
     * Method signature for max aggregation on query result
     */
    public const MAX = 'max';

    /**
     * Method signature for min aggregation on query result
     */
    public const MIN = 'min';

    /**
     * Method signature for avg aggregation on query result
     */
    public const AVERAGE = 'avg';

    /**
     * Method signature for sum aggregation on query result
     */
    public const SUM = 'sum';

}