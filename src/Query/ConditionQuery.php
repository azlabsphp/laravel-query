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

namespace Drewlabs\Packages\Database\Query;

use Drewlabs\Contracts\Data\Parser\QueryParser;
use Drewlabs\Core\Helpers\Iter;

class ConditionQuery implements QueryParser
{
    public function parse(array $params)
    {
        $islist = array_filter($params, 'is_array') === $params;

        return $islist ? iterator_to_array(
            Iter::map(
                new \ArrayIterator($params),
                function ($item) {
                    return $this->parseList($item);
                }
            )
        ) : $this->parseList($params);
    }

    private function parseList(array $params)
    {
        $fails = array_filter($params, static function ($item) {
            return null === $item || !isset($item);
        }) === $params;
        if ($fails) {
            throw new \InvalidArgumentException('Provided query parameters are not defined');
        }
        // Insure that where not working with associative arrays
        $params = array_values($params);
        // If the first value of the array is an array, parse it else return it
        $params[0] = \is_array($params[0]) && (isset($params[0]['model']) && $params[0]['column']) ? (new QueryAttribute($params[0]))->__toString() : $params[0];

        return $params;
    }
}
