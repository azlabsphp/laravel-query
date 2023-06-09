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

class Taggable extends Model implements Queryable
{
    use Compat;
    use TraitsModel;

    /**
     * @var array
     */
    public $relation_methods = ['tags'];

    /**
     * @var string
     */
    protected $table = 'taggables';

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
        'tag_id',
        'taggable_id',
        'taggable_type',
        'score',
    ];

    /**
     * Get all of the post's comments.
     */
    public function tags()
    {
    }
}
