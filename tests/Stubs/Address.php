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

namespace Drewlabs\Packages\Database\Tests\Stubs;

use Drewlabs\Packages\Database\Contracts\ORMModel;
use Drewlabs\Packages\Database\Traits\Queryable as TraitsModel;
use Illuminate\Database\Eloquent\Model;

class Address extends Model implements ORMModel
{
    use TraitsModel, Compat;

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
