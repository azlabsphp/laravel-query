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

namespace Drewlabs\Packages\Database\Eloquent;

use Drewlabs\Contracts\Data\Filters\FiltersInterface;
use Drewlabs\Contracts\Data\Parser\QueryParser;
use Drewlabs\Packages\Database\Eloquent\Traits\QueryFilters as QueryFiltersMixin;
use Drewlabs\Packages\Database\Query\JoinQuery;

final class QueryFilters implements FiltersInterface
{
    use QueryFiltersMixin;

    /**
     * @var QueryParser
     */
    private $joinQuery;

    /**
     * Creates class instance.
     *
     * @return self
     */
    public function __construct(array $filters = null, QueryParser $joinQuery = null)
    {
        $this->joinQuery = $joinQuery ?? new JoinQuery();
        $this->setQueryFilters($filters ?? []);
    }
}
