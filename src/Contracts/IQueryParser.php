<?php

namespace Drewlabs\Packages\Database\Contracts;

interface IQueryParser
{
    /**
     * Parse the provided parameters to the query param inputs
     *
     * @param array $params
     * @return array
     */
    public function parse(array $params);
}
