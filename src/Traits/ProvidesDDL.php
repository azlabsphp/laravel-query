<?php

declare(strict_types=1);

/*
 * This file is part of the drewlabs namespace.
 *
 * (c) Sidoine Azandrew <azandrewdevelopper@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drewlabs\Laravel\Query\Traits;

use Illuminate\Database\Eloquent\Model;

trait ProvidesDDL
{
    /**
     * Checks if the table configured for the current instance exists
     * in database.
     *
     * @throws QueryException
     *
     * @return bool
     */
    public static function tableExists()
    {
        $instance = self::newInstanceWithoutConstructor();

        return $instance
            ->getConnection()
            ->getSchemaBuilder()
            ->hasTable($instance->getTable());
    }

    /**
     * Checks if the table configured for the current instance has
     * user provided column in database.
     *
     * @throws QueryException
     *
     * @return bool
     */
    public static function hasColumn(string $column)
    {
        $instance = self::newInstanceWithoutConstructor();

        return $instance
            ->getConnection()
            ->getSchemaBuilder()
            ->hasColumn($instance->getTable(), $column);
    }

    /**
     * Checks if the table configured for the current instance has
     * list of user provided columns in database.
     *
     * @param array ...$columns
     *
     * @throws QueryException
     *
     * @return bool
     */
    public static function hasColumns($columns = [])
    {
        $instance = self::newInstanceWithoutConstructor();

        return $instance
            ->getConnection()
            ->getSchemaBuilder()
            ->hasColumns($instance->getTable(), \is_array($columns) ? $columns : \func_get_args());
    }

    /**
     * Creates a new builder instance.
     *
     * @throws \ReflectionException
     *
     * @return Model
     */
    public static function newInstanceWithoutConstructor()
    {
        return (new \ReflectionClass(__CLASS__))->newInstanceWithoutConstructor();
    }
}
