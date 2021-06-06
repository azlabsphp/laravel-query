<?php

namespace Drewlabs\Packages\Database;

use Drewlabs\Contracts\Data\DML\DMLProvider;
use Drewlabs\Contracts\Data\Model\Model;
use Illuminate\Support\Traits\ForwardsCalls;
use InvalidArgumentException;

class EloquentDMLQueryManager implements DMLProvider
{

    use ForwardsCalls;

    /**
     * The Eloquent model class binded to the current DML provider
     *
     * @var string
     */
    private $model_class;

    /**
     *
     * @var Model
     */
    private $model;

    public function __construct(Model|string $clazz)
    {
        if (is_string($clazz)) {
            $this->model_class = $clazz;
            // Create the model instance
        }

        if ($clazz instanceof Model) {
            $this->model = $clazz;
            $this->model_class = get_class($clazz);
        }

        throw new InvalidArgumentException("Constructor requires an instance of " . Model::class . ", or a Model class name");
    }

    public static function for($clazz)
    {
        return new static($clazz);
    }

    public function create(...$args) { }

    public function createMany(array $attributes) { }

    public function delete(...$args) { }

    public function select(...$args) { }

    public function update(...$params) { }
}
