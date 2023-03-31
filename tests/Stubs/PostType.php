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

class PostType extends Model implements ORMModel
{
    use TraitsModel, Compat;

    /**
     * @var array
     */
    public $relation_methods = ['posts'];

    /**
     * @var string
     */
    protected $table = 'post_types';

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
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'label'
    ];

    /**
     * Get all of the post type posts.
     */
    public function posts()
    {
        return $this->hasMany(Post::class, 'post_type_id', 'id');
    }
}
