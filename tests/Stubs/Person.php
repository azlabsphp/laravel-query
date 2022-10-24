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
use Drewlabs\Packages\Database\Traits\Model as TraitsModel;
use Illuminate\Database\Eloquent\Model;

class Person extends Model implements ORMModel
{
    use TraitsModel;

    /**
     * Model relations definitions.
     *
     * @var array
     */
    public $relation_methods = ['addresses', 'profile', 'managers'];

    /**
     * Model referenced table.
     *
     * @var string
     */
    protected $table = 'persons';

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
        'firstname',
        'lastname',
        'phonenumber',
        'age',
        'sex',
        'is_active',
        'email',
    ];

    public function addresses()
    {
        return $this->hasMany(Address::class, 'person_id', 'id');
    }

    public function profile()
    {
        return $this->hasOne(Profil::class, 'person_id', 'id');
    }

    public function managers()
    {
        return $this->belongsToMany(Manager::class, 'person_managers', 'person_id', 'manager_id', 'id', 'id');
        // ->using(PersonManager::class);
    }

    protected static function boot()
    {
        parent::boot();
        static::updating(static function (self $model) {
            exit();
        });
    }
}
