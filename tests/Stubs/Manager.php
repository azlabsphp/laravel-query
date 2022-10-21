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

class Manager extends Model implements ORMModel
{
    use TraitsModel;

    /**
     *
     * @var string
     */
    protected $table = 'managers';

    /**
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * 
     * @var array
     */
    protected $hidden = [];

    /**
     * 
     * @var array
     */
    protected $appends = [];

    /**
     *
     * @var array
     */
    public $relation_methods = ['persons'];

    /**
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'name',
        'position'
    ];

    /**
     * Get list of persons being managed
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
