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

namespace Drewlabs\Laravel\Query\Tests\Stubs;

use Drewlabs\Laravel\Query\Traits\Queryable as TraitsModel;
use Drewlabs\Query\Contracts\Queryable;
use Illuminate\Database\Eloquent\Model;

class Address extends Model implements Queryable
{
    use Compat;
    use TraitsModel;

    /**
     * Model referenced table.
     *
     * @var string
     */
    protected $table = 'addresses';

    /**
     * Unique identifier of table referenced by model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $hidden = [];

    protected $appends = [];

    /**
     * List of fillable properties of the current model.
     *
     * @var array
     */
    protected $fillable = [
        // ... Add fillable properties
        'postal_code',
        'country',
        'city',
        'email',
        'person_id',
    ];
}
