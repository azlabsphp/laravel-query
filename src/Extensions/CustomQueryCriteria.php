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

namespace Drewlabs\Packages\Database\Extensions;

use Drewlabs\Contracts\Data\Filters\FiltersInterface;
use Drewlabs\Contracts\Data\Parser\QueryParser;
use Drewlabs\Packages\Database\JoinQueryParamsParser;
use Drewlabs\Packages\Database\Traits\EloquentBuilderQueryFilters;

/**
 * @deprecated v2.0.x use {@see EloquentBuilderQueryFilters} implementation
 * @package Drewlabs\Packages\Database\Extensions
 */
final class CustomQueryCriteria implements FiltersInterface
{
    use EloquentBuilderQueryFilters;

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
