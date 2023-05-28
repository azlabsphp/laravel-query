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

namespace Drewlabs\LaravelQuery\Tests\Stubs;

use Drewlabs\LaravelQuery\Traits\Queryable as TraitsModel;
use Drewlabs\Query\Contracts\Queryable;
use Illuminate\Database\Eloquent\Model;

class Profil extends Model implements Queryable
{
    use Compat;
    use TraitsModel;

    /**
     * Model referenced table.
     *
     * @var string
     */
    protected $table = 'profiles';

    /**
     * Unique identifier of table referenced by model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * List of fillable properties of the current model.
     *
     * @var array
     */
    protected $fillable = [
        // ... Add fillable properties
        'url',
        'person_id',
    ];

    protected $relation_methods = [
        'person',
    ];

    public function person()
    {
        return $this->belongsTo(Person::class, 'person_id', 'id');
    }
}
