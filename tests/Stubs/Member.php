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

class Member extends Model implements Queryable
{
    use Compat;
    use TraitsModel;

    /**
     * @var array
     */
    public $relation_methods = [];

    /**
     * @var string
     */
    protected $table = 'members';

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
     * List of fillable properties of the current model.
     *
     * @var array
     */
    protected $fillable = [
        'distinctable_type',
        'distinctable_id',
        'phonenumber',
        'email',
    ];

    public function distinctable()
    {
        return $this->morphTo(__FUNCTION__, 'distinctable_type', 'distinctable_id');
    }
}
