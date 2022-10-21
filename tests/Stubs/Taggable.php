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

class Taggable extends Model implements ORMModel
{
    use TraitsModel;

    /**
     *
     * @var string
     */
    protected $table = 'taggables';

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
    public $relation_methods = ['tags'];

    /**
     *
     * @var array
     */
    protected $fillable = [
        'tag_id',
        'taggable_id',
        'taggable_type',
        'score'
    ];

    /**
     * Get all of the post's comments.
     */
    public function tags()
    {
    }

}
