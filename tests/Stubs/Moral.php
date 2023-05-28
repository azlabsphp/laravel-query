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

class Moral extends Model implements Queryable
{
    use Compat;
    use TraitsModel;

    /**
     * @var array
     */
    public $relation_methods = ['member'];

    /**
     * @var string
     */
    protected $table = 'morals';

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
        'label',
        'address',
    ];

    public function member()
    {
        return $this->morphOne(Member::class, 'distinctable');
    }
}
