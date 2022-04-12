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

/**
 * @internal API could be subject to change using PHP enumeration class
 */
class EloquentQueryBuilderMethods
{
    /**
     * @var string
     */
    public const UPSERT = 'updateOrCreate';

    /**
     * @var string
     */
    public const WHERE = 'where';

    /**
     * @var string
     */
    public const FIRST_OR_CREATE = 'firtOrCreate';

    /**
     * Create a new row in the database from attributes.
     */
    public const CREATE = 'create';

    /**
     * Persist the query builder to the database as row.
     */
    public const PERSIST = 'save';

    /**
     * Insert a list of rows to the database.
     */
    public const INSERT_MANY = 'insert';

    /**
     * Update row/rows in the database.
     */
    public const UPDATE = 'update';

    /**
     * Method signature for select operation on the database.
     */
    public const SELECT = 'get';

    /**
     * Method signature for selecting one row operation on the database.
     */
    public const SELECT_ONE = 'first';

    /**
     * Method signature for select operation with limit and skip clause on the database.
     */
    public const PAGINATE = 'paginate';

    /**
     * Method signature for delete operation on the database.
     */
    public const DELETE = 'delete';
}
