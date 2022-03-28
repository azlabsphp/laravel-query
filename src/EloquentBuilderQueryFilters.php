<?php

namespace Drewlabs\Packages\Database;

use Drewlabs\Contracts\Data\Filters\FiltersInterface;
use Drewlabs\Contracts\Data\Parser\QueryParser;
use Drewlabs\Packages\Database\Traits\EloquentBuilderQueryFilters as QueryFilters;

/**
 * 
 * @package Drewlabs\Packages\Database
 */
final class EloquentBuilderQueryFilters extends FiltersInterface
{
    use QueryFilters;

    /**
     * @var QueryParser
     */
    private $joinQueryParser;

    public function __construct(?array $filters = null, ?QueryParser $joinQueryParser = null)
    {
        $this->joinQueryParser = $joinQueryParser ?? new JoinQueryParamsParser();
        $this->setQueryFilters($filters ?? []);
    }
}
