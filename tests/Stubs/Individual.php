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

class Individual extends Model implements ORMModel
{
    use TraitsModel, Compat;

    /**
     * @var array
     */
    public $relation_methods = ['member'];

    /**
     * @var string
     */
    protected $table = 'individuals';

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
        'firstname',
        'lastname',
        'address',
        'sex',
    ];

    public function member()
    {
        return $this->morphOne(Member::class, 'distinctable');
    }
}
