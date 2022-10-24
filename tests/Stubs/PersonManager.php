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

class PersonManager extends Model implements ORMModel
{
    use TraitsModel;

    /**
     * @var array
     */
    public $relation_methods = ['person', 'manager'];

    /**
     * @var string
     */
    protected $table = 'person_managers';

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
        'person_id',
        'manager_id',
        'department',
    ];

    /**
     * Get attached person.
     */
    public function person()
    {
        return $this->belongsTo(Person::class, 'person_id', 'id');
    }

    /**
     * Get all of the tags for the post.
     */
    public function manager()
    {
        return $this->belongsTo(Manager::class, 'manager_id', 'id');
    }
}
