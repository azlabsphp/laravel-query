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

namespace Drewlabs\Packages\Database\Exceptions;

use Exception;

class ModelTypeException extends Exception
{

    public function __construct(array $possibleTypes = [], string $suffix = '')
    {
        $typeStr = implode(' or ', array_map(function ($type) {
            return is_object($type) ? get_class($type) : (is_string($type) ? $type : gettype($type));
        }, $possibleTypes));
        parent::__construct("Model must be of type $typeStr $suffix");
    }
}
