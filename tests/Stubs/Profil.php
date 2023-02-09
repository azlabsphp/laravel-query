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

class Profil extends Model implements ORMModel
{
    use TraitsModel;

    /**
     * Model referenced table.
     *
     * @var string
     */
    protected $table = 'profiles';

    /**
     * Unique identifier of table referenced by model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * List of fillable properties of the current model.
     *
     * @var array
     */
    protected $fillable = [
        // ... Add fillable properties
        'url',
        'person_id',
    ];

    protected $relation_methods = [
        'person',
    ];

    public function person()
    {
        return $this->belongsTo(Person::class, 'person_id', 'id');
    }

    public function getFillables()
    {
        return $this->getFillable();
    }
}
