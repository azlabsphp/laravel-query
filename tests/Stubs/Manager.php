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

class Manager extends Model implements Queryable
{
    use Compat;
    use TraitsModel;

    /**
     * @var array
     */
    public $relation_methods = ['persons'];

    /**
     * @var string
     */
    protected $table = 'managers';

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * @var array
     */
    protected $hidden = [];

    /**
     * @var array
     */
    protected $appends = [];

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'name',
        'position',
    ];

    /**
     * Get list of persons being managed.
     */
    public function persons()
    {
        return $this->belongsToMany(Person::class, 'person_managers', 'manager_id', 'person_id', 'id', 'id');
        // ->using(PersonManager::class);
    }

    public function person_manager()
    {
        return $this->hasOne(PersonManager::class, 'manager_id', 'id');
    }
}
