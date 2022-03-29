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

class DMLException extends \RuntimeException
{
    public function __construct($message = null, $code = 500, \Throwable $trace)
    {
        $message = $message ? sprintf('DMLManager Error %d: %s', $code, $message) : sprintf('Unknown DML Error %d', $code);
        parent::__construct($message, $code, $trace);
    }
}
