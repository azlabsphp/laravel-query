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

namespace Drewlabs\Packages\Database\Contracts;

interface ProvidesQueryLanguage
{
    /**
     * Provides implementation for creating new row application database
     *
     * @param array ...$args
     */
    public function create(...$args);

    /**
     * Provides query implementation that performs a delete query on database
     *
     * @param array ...$args
     */
    public function delete(...$args);

    /**
     * Provides query implementation that performs a select query on database
     *
     *
     * <code>
     * <?php
     *      $instance->select($query, $columns, false, function(QueryResultInterface $result ) {
     *          // Code to perform transformation
     *          return $transformed_values;
     *      })
     * ?>
     * </code>
     *
     * <code>
     * <?php
     *      $instance->select($id, $columns, false, function(Model $model ) {
     *          // Code to perform transformation
     *          return $transformed_model;
     *      })
     * ?>
     * </code>
     *
     * @param array ...$args
     */
    public function select(...$args);

    /**
     * Provides update query implementation
     * 
     * @param mixed ...$params 
     * @return mixed 
     */
    public function update(...$params);

    /**
     * Run an aggregation method on a query builder result.
     *
     * @return int|mixed
     */
    public function aggregate(array $query = [], string $method = 'count');
}
