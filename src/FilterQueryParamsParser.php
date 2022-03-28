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

use Drewlabs\Contracts\Data\Parser\QueryParser;

use function Drewlabs\Packages\Database\Proxy\QueryParam;

class FilterQueryParamsParser implements QueryParser
{
    /**
     * {@inheritDoc}
     */
    public function parse(array $params)
    {
        $isArrayList = array_filter($params, 'is_array') === $params;

        return $isArrayList ? iterator_to_array(
            drewlabs_core_iter_map(
                new \ArrayIterator($params),
                function ($item) {
                    return $this->parseList($item);
                }
            )
        ) : $this->parseList($params);
    }

    private function parseList(array $params)
    {
        $allEntiresAreNull = array_filter($params, static function ($item) {
            return null === $item || !isset($item);
        }) === $params;
        if ($allEntiresAreNull) {
            throw new \InvalidArgumentException('Provided query parameters are not defined');
        }
        // Insure that where not working with associative arrays
        $params = array_values($params);
        // If the first value of the array is an array, parse it else return it
        $params[0] = \is_array($params[0]) && (isset($params[0]['model']) && $params[0]['column']) ?
            (string) QueryParam($params[0]) :
            $params[0];

        return $params;
    }
}
