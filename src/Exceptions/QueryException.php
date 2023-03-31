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

use Throwable;

class QueryException extends \RuntimeException
{
    /**
     * 
     * @var int|string
     */
    private $queryErrorCode;

    /**
     * Creates exception instance
     * 
     * @param string $message 
     * @param int $code 
     * @param null|Throwable $trace 
     * @return void 
     */
    public function __construct($message = null, $code = 500, \Throwable $trace = null)
    {
        $message = $message ? sprintf('Error %d: %s', $code, $message) : sprintf('Unknown Error %d', $code);
        parent::__construct($message, 500, $trace);
        $this->queryErrorCode = $code;
    }

    /**
     * Returns the db query error code
     * 
     * @return int|string 
     */
    public function getQueryErrorCode()
    {
        return $this->queryErrorCode;
    }
}
