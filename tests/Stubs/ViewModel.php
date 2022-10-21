<?php

namespace Drewlabs\Packages\Database\Tests\Stubs;

trait ViewModel
{

    public function get($key)
    {
        return $this->inputs[$key] ?? null;
    }

    public function has($key)
    {
        return isset($this->inputs[$key]);
    }

    public function all()
    {
        return $this->inputs;
    }

}