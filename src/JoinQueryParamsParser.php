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

class JoinQueryParamsParser implements QueryParser
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
            return null === $item || !isset(
                $item
            );
        }) === $params;
        if ($allEntiresAreNull) {
            // dd($params);
            throw new \InvalidArgumentException('Provided query parameters are not defined');
        }
        // Insure that where not working with associative arrays
        $params = array_values($params);
        // Case the operator part if missing
        if (3 === \count($params)) {
            $params[0] = (\is_string($params[0]) && !class_exists($params[0])) ? $params[0] : (string) (QueryParam(
                \is_array($params[0]) ? $params[0] : ['model' => $params[0]]
            ));
            $params[1] = \is_string($params[1]) ? $params[1] : (string) (QueryParam($params[1]));
            $params[2] = \is_string($params[2]) ? $params[2] : (string) (QueryParam($params[2]));
            array_splice($params, 2, 1, ['=', $params[2]]);
        } else {
            $params[0] = (\is_string($params[0]) && !class_exists($params[0])) ? $params[0] : (QueryParam(
                \is_array($params[0]) ? $params[0] : ['model' => $params[0]]
            ));
            $params[1] = \is_string($params[1]) ? $params[1] : (string) (QueryParam($params[1]));
            $params[3] = \is_string($params[3]) ? $params[3] : (string) (QueryParam($params[3]));
        }

        return $params;
    }
}
