<?php

namespace Drewlabs\Laravel\Query\Contracts;

use BadMethodCallException;

interface QueryInterface
{
    /**
     * Get query results as array
     * 
     * @return array 
     * @throws BadMethodCallException 
     */
    public function getResult(): array;
}